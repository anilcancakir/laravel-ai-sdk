<?php

namespace Laravel\Ai\Gateway;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Gateway\ImageGateway;
use Laravel\Ai\Contracts\Providers\ImageProvider;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Files\Image as ImageFile;
use Laravel\Ai\Files\LocalImage;
use Laravel\Ai\Files\StoredImage;
use Laravel\Ai\Responses\Data\GeneratedImage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\ImageResponse;

class OpenAiCompatibleImageGateway implements ImageGateway
{
    use Concerns\HandlesRateLimiting;

    /**
     * Generate an image.
     *
     * @param  array<ImageFile>  $attachments
     * @param  '3:2'|'2:3'|'1:1'  $size
     * @param  'low'|'medium'|'high'  $quality
     */
    public function generateImage(
        ImageProvider $provider,
        string $model,
        string $prompt,
        array $attachments = [],
        ?string $size = null,
        ?string $quality = null,
        ?int $timeout = null,
    ): ImageResponse {
        $messages = $this->buildMessages($prompt, $attachments);

        $response = $this->withRateLimitHandling(
            $provider->name(),
            fn () => $this->client($provider, $timeout)
                ->post('/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                ])
                ->throw()
        );

        return $this->parseResponse($response->json(), $provider, $model);
    }

    /**
     * Build the messages array for the chat completions request.
     *
     * @param  array<ImageFile>  $attachments
     */
    protected function buildMessages(string $prompt, array $attachments): array
    {
        $content = [];

        $content[] = ['type' => 'text', 'text' => $prompt];

        foreach ($attachments as $attachment) {
            $content[] = match (true) {
                $attachment instanceof Base64Image => [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => "data:{$attachment->mime};base64,{$attachment->base64}",
                    ],
                ],
                $attachment instanceof LocalImage, $attachment instanceof StoredImage => [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => 'data:'.($attachment->mimeType() ?? 'image/png').';base64,'.base64_encode($attachment->content()),
                    ],
                ],
                default => throw new InvalidArgumentException(
                    'Unsupported attachment type ['.$attachment::class.']'
                ),
            };
        }

        return [
            ['role' => 'user', 'content' => $content],
        ];
    }

    /**
     * Parse the API response into an ImageResponse.
     */
    protected function parseResponse(array $data, ImageProvider $provider, string $model): ImageResponse
    {
        $message = data_get($data, 'choices.0.message', []);

        $images = new Collection;

        foreach (data_get($message, 'images', []) as $image) {
            $dataUrl = data_get($image, 'image_url.url');

            if ($dataUrl === null) {
                continue;
            }

            [$mime, $base64] = $this->parseDataUrl($dataUrl);

            $images->push(new GeneratedImage($base64, $mime));
        }

        $usage = new Usage(
            promptTokens: (int) data_get($data, 'usage.prompt_tokens', 0),
            completionTokens: (int) data_get($data, 'usage.completion_tokens', 0),
        );

        return new ImageResponse(
            $images,
            $usage,
            new Meta($provider->name(), $model),
        );
    }

    /**
     * Parse a data URL into its MIME type and base64 content.
     *
     * @return array{0: string, 1: string}
     */
    protected function parseDataUrl(string $dataUrl): array
    {
        if (preg_match('#^data:([^;]+);base64,(.+)$#', $dataUrl, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return ['image/png', $dataUrl];
    }

    /**
     * Get an HTTP client for the OpenAI compatible API.
     */
    protected function client(ImageProvider $provider, ?int $timeout = null): PendingRequest
    {
        return Http::baseUrl($provider->additionalConfiguration()['url'] ?? 'https://api.openai.com/v1')
            ->withHeaders([
                'Authorization' => 'Bearer '.$provider->providerCredentials()['key'],
                'Content-Type' => 'application/json',
            ])
            ->timeout($timeout ?? 120);
    }
}
