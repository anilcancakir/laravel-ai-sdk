<p align="center"><img src="/art/logo.svg" alt="Laravel AI SDK Plus"></p>

# Laravel AI SDK Plus

A fork of the official [Laravel AI SDK](https://github.com/laravel/ai) enriched with additional features. This package is kept in sync with the upstream `laravel/ai` repository as closely as possible — all core functionality remains identical.

## Installation

```bash
composer require anilcancakir/laravel-ai-sdk-plus
```

> **Note:** This package replaces `laravel/ai`. You should not have both installed at the same time.

## What's Different?

Everything from the official Laravel AI SDK works exactly the same. This fork adds the following features on top:

### Agent Skills System

A file-based, discoverable system that allows AI agents to dynamically load domain-specific instructions and reference files at runtime. Skills are Markdown files with YAML front matter, placed in `resources/skills/`.

```php
class DesignAgent extends Agent
{
    use Skillable;

    public function skills(): iterable
    {
        return ['wind-ui'];
    }
}
```

### OpenAI-Compatible Provider

Support for any OpenAI-compatible API endpoint (LocalAI, Ollama, vLLM, LiteLLM, etc.) as a first-class provider.

```php
// config/ai.php
'providers' => [
    'my-provider' => [
        'driver' => 'openai-compatible',
        'key' => env('MY_PROVIDER_API_KEY'),
        'url' => 'https://api.my-provider.com/v1',
        'models' => [
            'default' => 'gpt-4o',
            'image' => 'image-model',
        ],
    ],
],
```

#### Text Generation

```php
use Laravel\Ai\Ai;

$response = Ai::textProvider('my-provider')
    ->prompt('Explain quantum computing in one sentence.');
```

#### Image Generation

Generate images through any OpenAI-compatible service that supports image generation via the chat completions endpoint:

```php
use Laravel\Ai\Image;

// Basic image generation
$response = Image::of('A cat wearing a top hat')->generate('my-provider');

// Access the generated image
$response->firstImage()->image; // Base64 content
$response->firstImage()->mime;  // e.g. 'image/png'

// Save to disk
$response->store('images', 'public');

// With size and quality options
Image::of('A futuristic cityscape')
    ->landscape()
    ->quality('high')
    ->generate('my-provider');

// With reference image attachments
use Laravel\Ai\Files\Image as ImageFile;

Image::of('Make this image more vibrant')
    ->attachments([
        ImageFile::fromPath('/path/to/reference.jpg'),
    ])
    ->generate('my-provider');
```

Set a default image provider to skip specifying it every time:

```php
// config/ai.php
'default_for_images' => 'my-provider',

// Then simply:
Image::of('A mountain at dawn')->generate();
```

## Documentation

For core SDK documentation, refer to the official [Laravel AI SDK docs](https://laravel.com/docs/ai-sdk).

## Upstream Sync

This fork tracks the `0.x` branch of `laravel/ai`. Upstream changes are merged regularly to stay current with the official release.

## License

Laravel AI SDK Plus is open-sourced software licensed under the [MIT license](LICENSE.md).
