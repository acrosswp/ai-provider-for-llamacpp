# llama.cpp — Getting Started Guide

Set up llama.cpp as a local AI provider for WordPress.

## Table of Contents

1. [Install llama.cpp](#1-install-llamacpp)
2. [Download a Model](#2-download-a-model)
3. [Running the Model](#3-running-the-model)
4. [Connect WordPress to the Server](#4-connect-wordpress-to-the-server)
   - [A. Same Machine (Localhost)](#a-same-machine-localhost)
   - [B. Same Network (LAN)](#b-same-network-lan)
   - [C. Remote Server (Internet)](#c-remote-server-internet)
5. [Best Practices](#5-best-practices)

---

## 1. Install llama.cpp

Install on macOS using Homebrew:

```
brew install llama.cpp
```

Verify it works:

```
llama-server --help
```

You can also [build from source](https://llama-cpp.com/download/):

```
git clone https://github.com/ggml-org/llama.cpp.git
cd llama.cpp
cmake -B build
cmake --build build --config Release
```

> **Note:** For the latest updates or any issues, read the official docs: [llama-cpp.com/download](https://llama-cpp.com/download/)

---

## 2. Download a Model

**GGUF** is the binary format llama.cpp uses. Models are available on [Hugging Face](https://huggingface.co) in different quantization levels:

| Quantization | Size     | Quality | Speed    |
|--------------|----------|---------|----------|
| `Q2_K`       | Smallest | Lower   | Fastest  |
| `Q4_K_M`     | Small    | Good    | Fast     |
| `Q5_K_M`     | Medium   | Better  | Moderate |
| `Q8_0`       | Largest  | Best    | Slowest  |

Install the Hugging Face CLI (also known as `hf`) and download a model:

```
pip install -U huggingface_hub

huggingface-cli download \
  TheBloke/TinyLlama-1.1B-Chat-GGUF \
  tinyllama-1.1b-chat-v1.0.Q4_K_M.gguf \
  --local-dir ~/models \
  --local-dir-use-symlinks False
```

Verify the download:

```
ls -lh ~/models/tinyllama-1.1b-chat-v1.0.Q4_K_M.gguf
```

### Recommended starter models

| Model                      | Size     | Good for                              |
|----------------------------|----------|---------------------------------------|
| TinyLlama 1.1B (Q4_K_M)   | ~636 MB  | Testing, low-resource machines        |
| Phi-3 Mini 3.8B (Q4_K_M)  | ~2.2 GB  | Balance of speed and quality          |
| Mistral 7B (Q4_K_M)       | ~4.1 GB  | High quality, needs more RAM          |
| Llama 3 8B (Q4_K_M)       | ~4.7 GB  | Best quality, needs GPU or 16GB+ RAM  |

---

## 3. Running the Model

Start the llama.cpp server with your models directory:

```
llama-server --models-dir ~/models
```

### Common flags

| Flag              | What it does                                                      |
|-------------------|-------------------------------------------------------------------|
| `--models-dir`    | Directory containing GGUF files (auto-loads all)                  |
| `-c`              | Context size in tokens (512, 2048, 4096...)                       |
| `-t`              | CPU threads for inference                                         |
| `-b`              | Batch size for prompt processing                                  |
| `--host`          | `127.0.0.1` = localhost only, `0.0.0.0` = all interfaces         |
| `--port`          | HTTP server port (default 8080)                                   |
| `--api-key`       | Comma-separated API keys for authentication                      |
| `--n-gpu-layers`  | Offload layers to GPU (faster on supported hardware)              |

Test the server:

```
curl http://127.0.0.1:8080/v1/models
```

You should see a JSON response listing the loaded model(s). You can also open `http://127.0.0.1:8080/` in your browser to see the built-in chat UI.

---

## 4. Connect WordPress to the Server

Set the **Server URL** in the plugin settings based on where llama.cpp is running relative to your WordPress site.

| Setup             | Server URL                                  | Best for                              |
|-------------------|---------------------------------------------|---------------------------------------|
| **Same Machine**  | `http://127.0.0.1:8080`                     | Local development, simplest setup     |
| **Same Network**  | `http://192.168.x.x:8080`                   | Dedicated GPU machine on your LAN     |
| **Remote Server** | `https://your-tunnel.trycloudflare.com`     | Cloud server or sharing with others   |

### A. Same Machine (Localhost)

WordPress and llama.cpp run on the **same computer**. This is the simplest setup.

Start the server:

```
llama-server --models-dir ~/models
```

**Plugin setting:** Set the Server URL to `http://127.0.0.1:8080` (or leave empty for the default).

### B. Same Network (LAN)

llama.cpp runs on a **different machine** on your local network (e.g. a desktop with a GPU).

**Step 1 — Start the server** on the machine with the model. Use `--host 0.0.0.0` to accept network connections:

```
llama-server \
  --models-dir ~/models \
  --host 0.0.0.0
```

**Step 2 — Find the server's local IP:**

```
# macOS
ipconfig getifaddr en0

# Linux
hostname -I
```

**Step 3 — Test from the WordPress machine:**

```
curl http://192.168.x.x:8080/v1/models
```

**Plugin setting:** Set the Server URL to `http://<server-ip>:8080` (e.g. `http://192.168.1.50:8080`).

> **Tip:** Both machines must be on the same network. Make sure the port is not blocked by a firewall.

### C. Remote Server (Internet)

llama.cpp runs on a **remote machine** (cloud VPS, office server, etc.) and is exposed to the internet via a secure tunnel.

**Step 1 — Start the server** with authentication:

```
llama-server \
  --models-dir ~/models \
  --host 0.0.0.0 \
  --api-key your-secret-key
```

> **Security:** Always use `--api-key` when exposing the server to the internet. Never run a public server without authentication.

**Step 2 — Create a tunnel** (if no public IP). Pick one of the options below:

#### Option 1: Cloudflare Tunnel

Cloudflare Tunnel provides a free, stable URL with built-in DDoS protection.

**Install `cloudflared`:**

```
# macOS (Homebrew)
brew install cloudflared

# Linux (Debian/Ubuntu)
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb -o cloudflared.deb
sudo dpkg -i cloudflared.deb
```

> **Note:** For other platforms, see the [official downloads page](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/).

**Quick tunnel** (no account needed):

```
cloudflared tunnel --url http://localhost:8080
```

This gives you a public HTTPS URL like `https://something-random.trycloudflare.com`.

**Named tunnel** (stable URL, requires a free [Cloudflare account](https://dash.cloudflare.com/sign-up) and a domain):

```
# One-time setup
cloudflared tunnel login
cloudflared tunnel create llama
cloudflared tunnel route dns llama llama.yourdomain.com

# Run the tunnel
cloudflared tunnel run --url http://localhost:8080 llama
```

> **Tip:** Named tunnels give you a permanent URL that survives restarts. Quick tunnels generate a new random URL each time.

#### Option 2: ngrok

ngrok is a popular alternative that requires a free account.

**Install ngrok:**

```
# macOS (Homebrew)
brew install ngrok

# Linux (snap)
snap install ngrok

# Or download from https://ngrok.com/download
```

**Set up and run:**

Sign up at [ngrok.com](https://dashboard.ngrok.com/signup) and add your auth token:

```
ngrok config add-authtoken YOUR_AUTH_TOKEN
ngrok http 8080
```

This gives you a public HTTPS URL like `https://abc123.ngrok-free.app`.

> **Note:** Free ngrok URLs change on every restart. For a stable URL, use a paid ngrok plan or Cloudflare named tunnels.

**Step 3 — Plugin setting:** Set the Server URL to your tunnel URL (e.g. `https://something-random.trycloudflare.com` or `https://abc123.ngrok-free.app`).

---

## 5. Best Practices

### Security

- Use `--api-key` when sharing the server
- Never expose endpoints publicly without authentication
- Consider a reverse proxy (nginx, Caddy) for rate limiting and TLS

### Performance

- Smaller models (1–3B) are much faster than larger ones (7–8B)
- Lower quantization (Q2, Q4) = faster and less RAM; higher (Q8) = better quality
- Use `--n-gpu-layers` for GPU offloading on supported hardware
- Increase `-c` only as needed — larger context uses more memory

### WordPress Integration

- The plugin auto-discovers models from your server — no manual config needed
- Model list is cached for 1 hour; save settings to refresh
