# Agent Conversation Log and Plugin Overview

## Conversation Summary
This file documents the key decisions, changes, and context from the ongoing development and troubleshooting of the AI Provider for llama.cpp WordPress plugin. It is intended to help future maintainers and contributors understand the rationale behind recent changes and the current state of the codebase.

### Key Topics Covered
- Simplification of the models table (WP_List_Table)
- AJAX-powered model details modal with fallback logic
- Handling of remote vs local llama.cpp server metadata
- Documentation and README updates
- Connectors page: API key input enable/disable logic
- Provider availability logic and its effect on the Connectors UI
- Reverting to original "Connected" behavior for user clarity

## Recent Conversation Highlights
- The Connectors page disables the API key input if the provider is considered "configured" (i.e., the server responds, regardless of API key presence).
- A custom ProviderAvailability class was briefly introduced to always enable the input, but this also removed the "Connected" status. After user review, this was reverted.
- The plugin now uses the default ListModelsApiBasedProviderAvailability, so the Connectors page shows "Connected" when the llama.cpp server responds, and disables the input as before.
- The API key is optional for local servers, but required for remote servers with auth enabled.

## Plugin Structure Overview
- **ai-provider-for-llamacpp.php**: Main plugin loader and requirements check.
- **src/Plugin.php**: Registers the provider, fallback auth, and settings. Handles WordPress hooks and filters.
- **src/Provider/LlamaCppProvider.php**: Main provider class, implements OpenAI-compatible API logic for llama.cpp.
- **src/Settings/LlamaCppSettings.php**: Admin settings page for configuring the server URL and AJAX model details.
- **src/Settings/ModelsListTable.php**: Renders the models table in the admin UI.
- **docs/getting-started.html**: Step-by-step setup and usage guide.
- **README.md / readme.txt**: Project overview, requirements, and installation instructions.

## Current Behavior (as of April 2026)
- The Connectors page shows "Connected" and disables the API key input if the llama.cpp server responds, regardless of whether an API key is set.
- To change the API key, the user must disconnect and reconnect the provider.
- Local servers work without an API key; remote servers may require one.

---
This file is auto-generated to capture the latest context and decisions. Update as needed to reflect future changes or major architectural decisions.

