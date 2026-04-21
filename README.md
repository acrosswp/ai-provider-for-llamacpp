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

For the full in-admin guide see [docs/getting-started.html](docs/getting-started.html). The steps below cover the complete setup end-to-end.

### Step 1 — Install llama.cpp

**macOS (Homebrew):**

```bash
brew install llama.cpp
```

**Build from source:**

```bash
git clone https://github.com/ggml-org/llama.cpp.git
cd llama.cpp
cmake -B build
cmake --build build --config Release
```

Verify the install:

```bash
llama-server --help
```

> For the latest instructions see [llama-cpp.com/download](https://llama-cpp.com/download/).

---

### Step 2 — Download a Model

llama.cpp uses the **GGUF** binary format. Models are available on [Hugging Face](https://huggingface.co) in several quantization levels:

| Quantization | Size    | Quality | Speed    |
|-------------|---------|---------|----------|
| `Q2_K`      | Smallest| Lower   | Fastest  |
| `Q4_K_M`   | Small   | Good    | Fast     |
| `Q5_K_M`   | Medium  | Better  | Moderate |
| `Q8_0`      | Largest | Best    | Slowest  |

Install the Hugging Face CLI and download a starter model:

```bash
pip install -U huggingface_hub

huggingface-cli download \
  TheBloke/TinyLlama-1.1B-Chat-GGUF \
  tinyllama-1.1b-chat-v1.0.Q4_K_M.gguf \
  --local-dir ~/models \
  --local-dir-use-symlinks False
```

**Recommended starter models:**

| Model | Size | Good for |
|-------|------|----------|
| TinyLlama 1.1B (Q4_K_M) | ~636 MB | Testing, low-resource machines |
| Phi-3 Mini 3.8B (Q4_K_M) | ~2.2 GB | Balance of speed and quality |
| Mistral 7B (Q4_K_M) | ~4.1 GB | High quality, needs more RAM |
| Llama 3 8B (Q4_K_M) | ~4.7 GB | Best quality, needs GPU or 16 GB+ RAM |

---

### Step 3 — Start the llama.cpp Server

```bash
llama-server --models-dir ~/models
```

**Common flags:**

| Flag | What it does |
|------|-------------|
| `--models-dir` | Directory containing GGUF files (auto-loads all) |
| `-c` | Context size in tokens (512, 2048, 4096…) |
| `-t` | CPU threads for inference |
| `--host` | `127.0.0.1` = localhost only, `0.0.0.0` = all interfaces |
| `--port` | HTTP server port (default 8080) |
| `--api-key` | Comma-separated API keys for authentication |
| `--n-gpu-layers` | Offload layers to GPU (faster on supported hardware) |

Test the server is running:

```bash
curl http://127.0.0.1:8080/v1/models
```

You should see a JSON list of loaded models. You can also open `http://127.0.0.1:8080/` in a browser to use the built-in chat UI.

---

### Step 4 — Connect WordPress to the Server

Set the **Server URL** based on where llama.cpp runs relative to your WordPress site:

| Setup | Server URL | Best for |
|-------|-----------|----------|
| Same machine | `http://127.0.0.1:8080` | Local development |
| Same network (LAN) | `http://192.168.x.x:8080` | Dedicated GPU machine |
| Remote server | `https://your-tunnel.trycloudflare.com` | Cloud VPS or sharing |

#### A. Same Machine (Localhost)

The default. Start the server and leave the plugin URL at `http://127.0.0.1:8080` (or leave blank).

#### B. Same Network (LAN)

Start the server on the GPU machine with `--host 0.0.0.0`:

```bash
llama-server --models-dir ~/models --host 0.0.0.0
```

Find the server IP:

```bash
# macOS
ipconfig getifaddr en0

# Linux
hostname -I
```

Set the plugin Server URL to `http://<server-ip>:8080`.

#### C. Remote Server (Internet)

Start the server with an API key:

```bash
llama-server --models-dir ~/models --host 0.0.0.0 --api-key your-secret-key
```

> **Security:** Always use `--api-key` when exposing the server to the internet.

Create a tunnel with **Cloudflare Tunnel** (free, no account needed for quick tunnels):

```bash
# Install
brew install cloudflared   # macOS
# or: https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/

# Quick tunnel (new URL on each restart)
cloudflared tunnel --url http://localhost:8080
```

Or use **ngrok**:

```bash
brew install ngrok          # macOS
ngrok config add-authtoken YOUR_AUTH_TOKEN
ngrok http 8080
```

Set the plugin Server URL to the tunnel HTTPS URL.

---

### Step 5 — Activate and Configure the Plugin

1. Install and activate the **WordPress AI Client** plugin.
2. Install and activate **AI Provider for llama.cpp**.
3. Go to **Settings > llama.cpp**.
4. Set the **Server URL** (leave blank for the default `http://127.0.0.1:8080`).
5. Save changes — the plugin auto-discovers models from your server.

---

### Best Practices

**Security**
- Use `--api-key` whenever sharing or exposing the server.
- Consider a reverse proxy (nginx, Caddy) for rate limiting and TLS.

**Performance**
- Smaller models (1–3 B) are much faster than larger ones (7–8 B).
- Lower quantization (Q2, Q4) = faster and less RAM; higher (Q8) = better quality.
- Use `--n-gpu-layers` for GPU offloading on supported hardware.
- Increase `-c` (context size) only as needed — larger context uses more memory.

**WordPress Integration**
- The plugin auto-discovers models — no manual model configuration needed.
- The model list is cached for 1 hour; save settings to refresh it immediately.

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
