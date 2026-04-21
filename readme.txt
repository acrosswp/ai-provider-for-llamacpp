=== AI Provider for llama.cpp ===
Contributors: acrosswp
Tags: ai, llamacpp, llm, local-ai, connector
Requires at least: 7.0
Tested up to: 7.0
Stable tag: 0.0.2
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

llama.cpp provider for the WordPress AI Client.

== Description ==

This plugin provides llama.cpp integration for the WordPress AI Client. It lets WordPress sites use large language models running via a llama.cpp server for text generation and other AI capabilities.

llama.cpp exposes an OpenAI-compatible API, and this provider uses that API to communicate with any GGUF model loaded into your llama.cpp server.

**Features:**

* Text generation with any llama.cpp-loaded model
* Automatic model discovery from your llama.cpp server
* Function calling support
* Structured output (JSON mode) support
* Settings page to configure the server URL (default: http://127.0.0.1:8080)
* Works without an API key for local instances

**Requirements:**

* PHP 7.4 or higher
* WordPress AI Client plugin must be installed and activated
* llama.cpp server running locally or on a remote host

== Getting Started ==

= What do I need before using this plugin? =

You need a running llama.cpp server (local or remote) and the WordPress AI Client plugin installed and activated.

= How do I install llama.cpp? =

On macOS run `brew install llama.cpp`. For other platforms, see the official docs at https://llama-cpp.com/download/

= Where do I get a model? =

Download a GGUF model from Hugging Face. TinyLlama 1.1B (Q4_K_M, ~636 MB) is a good starting point for testing.

= How do I start the server? =

Run `llama-server --models-dir ~/models`. The server starts on `http://127.0.0.1:8080` by default.

= How do I connect WordPress to my llama.cpp server? =

Go to **Settings > llama.cpp** and enter your server URL. Leave it blank to use the default (`http://127.0.0.1:8080`).

For full step-by-step setup instructions, see the [Getting Started Guide](https://github.com/acrosswp/ai-provider-for-llamacpp/).

== Installation ==

1. Ensure the WordPress AI Client plugin is installed and activated.
2. Upload the plugin files to `/wp-content/plugins/ai-provider-for-llamacpp/`.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Go to **Settings > llama.cpp** to configure the server URL.

== Frequently Asked Questions ==

= Do I need an API key? =

No. For local llama.cpp instances, no API key is needed. The plugin automatically registers an empty key as a fallback.

= What is the default server URL? =

`http://127.0.0.1:8080` -- the default address used by `llama-server`.

= How do I change the server URL? =

Go to **Settings > llama.cpp** and enter your server's base URL in the "Server URL" field.

== Changelog ==

= 0.0.1 =
* Initial release
* Text generation with llama.cpp OpenAI-compatible API
* Settings page for server URL configuration

= 0.0.2 =
* Added documentation in the docs/ directory
* Fixed minor issues and small bugs
* Updated plugin icon and banner images
