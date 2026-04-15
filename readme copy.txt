=== AI Provider for llama.cpp ===
Contributors: acrosswp
Tags: ai, llamacpp, llm, local-ai, connector
Requires at least: 7.0
Tested up to: 7.0
Stable tag: 0.0.1
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
* WordPress 7.0+ with the core AI Client available
* llama.cpp server running locally or on a remote host

== External services ==

This plugin connects to a locally hosted llama.cpp server to provide AI model responses.

What the service is used for:
The plugin communicates with a locally running llama.cpp API server to retrieve AI-generated responses and model data.

What data is sent and when:
User prompts and configuration data are sent to the local API endpoint when generating responses.

Service location:
The service runs locally on the user's server (default: http://127.0.0.1:8080).

No data is transmitted to third-party external servers unless explicitly configured by the user.

Terms of service and privacy policy:
Since the service is self-hosted, users are responsible for their own data handling and compliance.

== Installation ==

1. Ensure you are running WordPress 7.0+ with the core AI Client available.
2. Upload the plugin files to `/wp-content/plugins/aipf-llamacpp/`.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Go to **Settings > llama.cpp** to configure the server URL.
5. Go to **Settings > Connectors** to confirm the `llama.cpp` provider is available in WordPress.

== Frequently Asked Questions ==

= Do I need an API key? =

No. For local llama.cpp instances, no API key is needed. The plugin automatically registers an empty key as a fallback.

= What is the default server URL? =

`http://127.0.0.1:8080` -- the default address used by `llama-server`.

= How do I change the server URL? =

Go to **Settings > llama.cpp** and enter your server's base URL in the "Server URL" field. The provider itself is listed on **Settings > Connectors**.

== Changelog ==

= 0.0.1 =
* Initial release
* Text generation with llama.cpp OpenAI-compatible API
* Settings page for server URL configuration
