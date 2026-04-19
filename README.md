# AI Provider for llama.cpp

An AI Provider for llama.cpp for the [PHP AI Client](https://github.com/WordPress/php-ai-client) SDK.
It can be used as a WordPress plugin and as a Composer package.

## What this plugin does

This provider connects WordPress AI Client to a llama.cpp server that exposes an OpenAI-compatible API.

Current capabilities include:

- Text generation using models exposed by your llama.cpp server
- Automatic model discovery from `GET /v1/models`
- Chat history support
- Function declaration support
- Structured output support (for example JSON responses)
- WordPress admin settings for the llama.cpp server URL
- Fallback auth registration so local setups can work without an API key

## Getting Started

For a complete setup guide — installing llama.cpp, downloading models, running the server, and connecting WordPress — see the [Getting Started Guide](docs/getting-started.html).

## Requirements

- PHP 7.4+
- WordPress 7.0+
- WordPress AI Client available in your WordPress installation
- A running llama.cpp server (local or remote) with OpenAI-compatible endpoints

Default server URL used by this plugin:

- `http://127.0.0.1:8080`

## Installation

### WordPress plugin

1. Install and activate the WordPress AI Client plugin.
2. Place this plugin in `wp-content/plugins/ai-provider-for-llamacpp`.
3. Activate **AI Provider for llama.cpp** from Plugins.
4. Open **Settings > llama.cpp** and set your server URL if needed.

### Composer package

```bash
composer require acrosswp/ai-provider-for-llamacpp
```

## Configuration

In wp-admin:

1. Go to **Settings > llama.cpp**.
2. Set **Server URL** (base URL of your llama.cpp server).
3. Save changes.

Notes:

- If the URL is empty, the plugin uses `http://127.0.0.1:8080`.
- The plugin trims trailing slashes when saving the URL.
- Updating settings clears the cached model list.

## Usage

### In WordPress

The provider registers itself automatically on plugin load.

```php
$result = wp_ai_client_prompt( 'Write a short product summary for hiking boots.' )
	->using_provider( 'llamacpp' )
	->generate_text();

if ( is_wp_error( $result ) ) {
	// Handle error.
}
```

You can also set preferred models explicitly (model IDs are fetched from your llama.cpp server):

```php
$result = wp_ai_client_prompt( 'Suggest 5 blog post titles about local AI.' )
	->using_provider( 'llamacpp' )
	->using_model_preference( array( 'llamacpp', 'your-model-id' ) )
	->generate_text();
```

### Standalone (SDK)

```php
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\LlamaCppAiProvider\Provider\LlamaCppProvider;

$registry = AiClient::defaultRegistry();
$registry->registerProvider( LlamaCppProvider::class );

// Optional for local llama.cpp. The WordPress plugin mode sets this fallback automatically.
$registry->setProviderRequestAuthentication( 'llamacpp', new ApiKeyRequestAuthentication( '' ) );

$result = AiClient::prompt( 'Explain retrieval augmented generation in simple terms.' )
	->usingProvider( 'llamacpp' )
	->generateTextResult();

echo $result->toText();
```

## Model discovery and caching

- The plugin requests models from `<base-url>/v1/models`.
- Preferred model IDs are cached in a transient (`ai_llamacpp_model_ids`).
- Successful responses are cached for 1 hour.
- Failure responses are cached for 30 seconds to avoid repeated failed requests.

## Security and networking notes

- The plugin allows HTTP requests to the configured llama.cpp host and port.
- It is designed for local network and development-style llama.cpp deployments.

## Development

Available composer scripts:

```bash
composer run lint
composer run phpcs
composer run phpstan
```

## License

GPL-2.0-or-later
