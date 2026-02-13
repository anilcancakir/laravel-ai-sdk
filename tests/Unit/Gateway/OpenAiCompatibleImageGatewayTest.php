<?php

namespace Tests\Unit\Gateway;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;
use Laravel\Ai\Contracts\Providers\ImageProvider;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Gateway\OpenAiCompatibleImageGateway;
use Laravel\Ai\Responses\Data\GeneratedImage;
use Laravel\Ai\Responses\ImageResponse;
use Tests\TestCase;

class OpenAiCompatibleImageGatewayTest extends TestCase
{
    private OpenAiCompatibleImageGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new OpenAiCompatibleImageGateway;

        Config::set('ai.providers.test-provider', [
            'driver' => 'openai-compatible',
            'key' => 'test-api-key',
            'url' => 'https://api.example.com/v1',
            'models' => [
                'image' => 'test-image-model',
            ],
        ]);
    }

    private function provider(): ImageProvider
    {
        return Ai::imageProvider('test-provider');
    }

    public function test_it_sends_correct_request_to_chat_completions_endpoint(): void
    {
        Http::fake([
            'api.example.com/v1/chat/completions' => Http::response($this->fakeApiResponse()),
        ]);

        $this->gateway->generateImage($this->provider(), 'image-model', 'A beautiful sunset');

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://api.example.com/v1/chat/completions'
                && $body['model'] === 'image-model'
                && $body['messages'][0]['role'] === 'user'
                && $body['messages'][0]['content'][0]['type'] === 'text'
                && $body['messages'][0]['content'][0]['text'] === 'A beautiful sunset';
        });
    }

    public function test_it_sends_authorization_header(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response($this->fakeApiResponse()),
        ]);

        $this->gateway->generateImage($this->provider(), 'image-model', 'Test prompt');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }

    public function test_it_parses_images_from_response(): void
    {
        $imageBase64 = base64_encode('fake-image-content');

        Http::fake([
            'api.example.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Here is your image',
                            'images' => [
                                [
                                    'image_url' => [
                                        'url' => "data:image/png;base64,{$imageBase64}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->gateway->generateImage(
            $this->provider(), 'image-model', 'Generate something'
        );

        $this->assertInstanceOf(ImageResponse::class, $response);
        $this->assertCount(1, $response->images);
        $this->assertInstanceOf(GeneratedImage::class, $response->firstImage());
        $this->assertEquals($imageBase64, $response->firstImage()->image);
        $this->assertEquals('image/png', $response->firstImage()->mime);
    }

    public function test_it_parses_multiple_images(): void
    {
        $image1 = base64_encode('image-1');
        $image2 = base64_encode('image-2');

        Http::fake([
            'api.example.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'images' => [
                                ['image_url' => ['url' => "data:image/jpeg;base64,{$image1}"]],
                                ['image_url' => ['url' => "data:image/png;base64,{$image2}"]],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->gateway->generateImage(
            $this->provider(), 'image-model', 'Two images please'
        );

        $this->assertCount(2, $response->images);
        $this->assertEquals($image1, $response->images[0]->image);
        $this->assertEquals('image/jpeg', $response->images[0]->mime);
        $this->assertEquals($image2, $response->images[1]->image);
        $this->assertEquals('image/png', $response->images[1]->mime);
    }

    public function test_it_handles_response_with_no_images(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Sorry, I cannot generate that image.',
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->gateway->generateImage(
            $this->provider(), 'image-model', 'Something impossible'
        );

        $this->assertCount(0, $response->images);
    }

    public function test_it_parses_usage_from_response(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response([
                'choices' => [
                    ['message' => ['images' => []]],
                ],
                'usage' => [
                    'prompt_tokens' => 42,
                    'completion_tokens' => 100,
                ],
            ]),
        ]);

        $response = $this->gateway->generateImage(
            $this->provider(), 'image-model', 'Test'
        );

        $this->assertEquals(42, $response->usage->promptTokens);
        $this->assertEquals(100, $response->usage->completionTokens);
    }

    public function test_it_sets_provider_and_model_in_meta(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response($this->fakeApiResponse()),
        ]);

        $response = $this->gateway->generateImage(
            $this->provider(), 'my-image-model', 'Test'
        );

        $this->assertEquals('test-provider', $response->meta->provider);
        $this->assertEquals('my-image-model', $response->meta->model);
    }

    public function test_it_includes_base64_image_attachments_in_request(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response($this->fakeApiResponse()),
        ]);

        $attachment = new Base64Image(base64_encode('reference-image'), 'image/jpeg');

        $this->gateway->generateImage(
            $this->provider(), 'image-model', 'Edit this image', [$attachment]
        );

        Http::assertSent(function ($request) {
            $content = $request->data()['messages'][0]['content'];

            return count($content) === 2
                && $content[0]['type'] === 'text'
                && $content[1]['type'] === 'image_url'
                && str_starts_with($content[1]['image_url']['url'], 'data:image/jpeg;base64,');
        });
    }

    public function test_it_handles_data_url_without_mime_prefix(): void
    {
        $rawBase64 = base64_encode('raw-image');

        Http::fake([
            'api.example.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'images' => [
                                ['image_url' => ['url' => $rawBase64]],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->gateway->generateImage(
            $this->provider(), 'image-model', 'Test'
        );

        $this->assertCount(1, $response->images);
        $this->assertEquals($rawBase64, $response->firstImage()->image);
        $this->assertEquals('image/png', $response->firstImage()->mime);
    }

    public function test_it_skips_images_with_null_urls(): void
    {
        $validBase64 = base64_encode('valid');

        Http::fake([
            'api.example.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'images' => [
                                ['image_url' => ['url' => null]],
                                ['image_url' => ['url' => "data:image/png;base64,{$validBase64}"]],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->gateway->generateImage(
            $this->provider(), 'image-model', 'Test'
        );

        $this->assertCount(1, $response->images);
        $this->assertEquals($validBase64, $response->firstImage()->image);
    }

    public function test_it_uses_default_base_url_when_url_not_configured(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response($this->fakeApiResponse()),
        ]);

        Config::set('ai.providers.no-url-provider', [
            'driver' => 'openai-compatible',
            'key' => 'test-key',
        ]);

        $provider = Ai::imageProvider('no-url-provider');

        $this->gateway->generateImage($provider, 'image-model', 'Test');

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.openai.com/v1/chat/completions');
        });
    }

    private function fakeApiResponse(): array
    {
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Generated image',
                        'images' => [
                            [
                                'image_url' => [
                                    'url' => 'data:image/png;base64,'.base64_encode('fake-image'),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
