# Agent Conversation Log and Plugin Technical Overview

## Plugin Purpose
This plugin connects WordPress to a running llama.cpp server (local or remote) via the WordPress AI Client SDK, exposing OpenAI-compatible LLM capabilities (text generation, chat, function calling, etc.) to WordPress and its plugins.

- Supports both local and remote llama.cpp servers
- Automatic model discovery from `/v1/models`
- Works with or without API key (local servers usually need none)
- Integrates with WordPress Connectors page and admin settings
- Provides fallback authentication for local dev

## Technical Architecture
- **Main entry:** `ai-provider-for-llamacpp.php` (loads autoloader, checks requirements)
- **Provider registration:** `src/Plugin.php` (registers provider, fallback auth, settings, hooks)
- **Provider logic:** `src/Provider/LlamaCppProvider.php` (OpenAI-compatible, model metadata, availability)
- **Settings UI:** `src/Settings/LlamaCppSettings.php` (admin page, AJAX model details, server URL)
- **Models table:** `src/Settings/ModelsListTable.php` (WP_List_Table for models, AJAX modal)
- **Docs:** `docs/getting-started.html` (step-by-step setup)
- **README.md/readme.txt:** Overview, requirements, install, usage

## Data Flow & Key Logic
- **Provider registration:**
  - On `init`, registers provider with AI Client SDK
  - Registers fallback auth (empty API key) for local servers
- **Connectors page:**
  - Uses `ListModelsApiBasedProviderAvailability` to check if provider is "configured" (server responds)
  - If so, shows "Connected" and disables API key input
  - To change key, user must disconnect and reconnect
- **Settings page:**
  - Lets user set llama.cpp server URL
  - Shows models table (AJAX-powered modal for details)
- **Model metadata:**
  - Fetched from `/v1/models` (router or sub-server)
  - Fallback chain: sub-server → /props?model=X → /slots → --ctx-size args
- **AJAX modal:**
  - Shows model details, with fallback for missing meta fields
  - Handles both local and remote servers

## Key Classes
- `Plugin`: Registers provider, fallback auth, settings, hooks/filters
- `LlamaCppProvider`: Implements provider interface, model metadata, availability
- `LlamaCppSettings`: Admin UI, AJAX handlers, server URL logic
- `ModelsListTable`: Renders models table, handles columns/actions
- `LlamaCppModelMetadataDirectory`: Handles model metadata fetching

## Decision History & Rationale
- **API key input logic:**
  - By default, Connectors disables input if server responds (even if no key set)
  - Custom ProviderAvailability was tested to always enable input, but removed "Connected" status
  - Reverted to default for user clarity: now shows "Connected" when server responds, disables input
- **Fallback auth:**
  - Always registers empty API key for local servers, so plugin works out of the box
- **AJAX modal:**
  - Uses fallback chain to get as much model info as possible, even if router omits meta
- **Docs:**
  - Getting Started guide covers install, model download, running, connecting, best practices

## Troubleshooting & Known Limitations
- **Connectors page:** Can't enable input and show "Connected" at the same time (WordPress core limitation)
- **Model meta:** Router omits some fields; plugin falls back to sub-server or props
- **Remote servers:** API key required if server is configured with `--api-key` (user must enter key)
- **Local dev:** Works without key; fallback auth ensures smooth experience

## Usage Examples
- See README.md for code samples (WordPress and standalone SDK)
- See docs/getting-started.html for full setup

## Contributors & Maintenance
- See GitHub repo for commit history and issues
- This file is auto-generated to capture the latest context and decisions. Update as needed for future changes.
