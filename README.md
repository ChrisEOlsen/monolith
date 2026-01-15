# Modern PHP Monolith (MCP-Native)

**The "Token Efficient" Stack for AI-Driven Development.**

This project is a rejection of modern complexity in favor of AI scalability. It implements a **Self-Replicating Monolith** where the AI Agent (running via MCP) lives *inside* the container, generating the very code that runs the application.

By combining the stability of **PHP 8.2** with the generative power of **Python + Jinja2**, and the interactivity of **HTMX**, we achieve a development loop with zero build steps, zero hydration errors, and maximum speed.

## 🧠 The Core Philosophy

### 1. Token Efficiency (The "Fuel" of AI)
In a traditional stack (Next.js + FastAPI), an AI agent must read 2,000+ tokens (Pydantic schemas, React props, API clients) just to understand a single feature.
* **This Stack:** The AI reads ~300 tokens. Logic, Data, and View are often in a single file or tightly coupled.
* **Result:** You fit **6x more features** into the AI's context window before it starts hallucinating. The patterns are linear, predictable, and "low perplexity."

### 2. Pattern Enforcement (Safety by Design)
Because we use **Jinja2 Templates** to generate code, the LLM never writes structural code from scratch—it only fills in the blanks.
* **Consistency:** Every single generated page follows the exact same architecture, naming convention, and layout. No more "spaghetti code" drift where the AI switches coding styles halfway through a project.
* **Security Guardrails:** The AI *cannot* write insecure SQL queries because the `class.php.j2` template hard-codes PDO Prepared Statements. The template enforces the security standard, not the prompt.

### 3. API Supremacy (Integration Made Easy)
PHP is the "Lingua Franca" of the web. Services like Stripe, Google, and AWS treat PHP as a first-class citizen with battle-tested SDKs that just work.
* **No "Glue" Code:** Authentication is handled via simple server-side Sessions, eliminating complex JWT/Frontend state synchronization.
* **Zero CORS Nightmares:** Your backend serves your frontend. API requests are local, meaning no pre-flight checks or cross-origin errors.

### 4. The "Training Data" Advantage
Large Language Models (LLMs) like Gemini and Claude have been trained on 25 years of PHP code—likely the largest corpus of web code in existence.
* **The Reality:** LLMs are statistically better at writing standard PHP/SQL than they are at writing the latest experimental Next.js App Router hooks.
* **The Benefit:** When you ask for a feature, the AI gets it right the first time because it's speaking its "native language."

### 5. Less is More (Zero Build Step)
We removed the fragility of the modern web:
* ❌ No `node_modules` (1GB+ of dependencies).
* ❌ No Webpack/Vite build steps.
* ❌ No "Hydration" or "Client/Server State" sync issues.
* ✅ **Just Files.** The AI writes a file, Apache serves it. You hit Refresh.

## 🚀 Features

* **Runtime:** PHP 8.2 + Apache (The "Engine").
* **Builder:** Python + FastMCP + Jinja2 (The "Factory" inside the container).
* **Frontend:** HTML + HTMX + Tailwind CSS (No JavaScript framework required).
* **Database:** MySQL 8.0 with PDO (Secure, raw SQL power).
* **Admin:** phpMyAdmin included for visual database management.
* **Edge:** Traefik + Cloudflare Tunnel (Enterprise-grade security, zero port forwarding).

## 🏗️ Architecture: "The Factory in the Building"

Unlike distributed microservices, this stack runs as a **Unified Monolith**.

```text
/mcp-monolith
 ├── src/
 │   ├── builder/        # 🔒 The AI's Workshop (Python + Jinja Templates)
 │   │   ├── mcp_server.py
 │   │   └── templates/  # "Golden Templates" for secure code gen
 │   ├── public/         # 🌍 The Live Application (PHP + HTML)
 │   │   ├── index.php
 │   │   └── style.css
 └── docker-compose.yml  # Orchestrates the magic
```

1. **The Prompt:** You tell the AI: _"Create a User Profile page."_
    
2. **The Action:** The **Builder** (Python) reads a `layout.php.j2` blueprint, fills it with logic, and writes `profile.php` to the **Public** folder.
    
3. **The Result:** Apache serves the file immediately. HTMX handles the interactions.
    

## 🏁 Getting Started

### Prerequisites

- [gemini-cli](https://github.com/google/gemini-cli)
- Docker & Docker Compose
- A Cloudflare Tunnel Token (optional, for public access)
    

### Installation

1. **Clone the repository:**
    
    ```bash
    git clone https://github.com/ChrisEOlsen/ai-first-php-monolith.git
    cd ai-first-php-monolith
    ```
    
2. **Start the Monolith:**
    
    ```bash
    docker compose up -d --build
    ```
    
3. **Access the Application:**
    
    - **App:** `http://localhost`
    - **Database Admin:** `http://localhost:8080` (phpMyAdmin)
        

## 🤖 MCP Integration

To enable your AI agent (Claude Desktop, Cursor, Gemini CLI) to build this project, add this configuration.

Note: We execute the MCP server INSIDE the running container to give the AI direct access to the environment.

**`settings.json`:**

```json
"php-monolith-builder": {
  "command": "docker",
  "args": [
    "exec",
    "-i",
    "-u",
    "www-data",
    "php-monolith-app",
    "/opt/builder_venv/bin/python",
    "/var/www/html/builder/mcp_server.py"
  ]
}
```

## 🛡️ Security

- **Supply Chain:** We use **Zero** npm dependencies. The attack surface is limited to the OS and PHP, both of which are stable and patched.
- **SQL Injection:** The AI uses "Golden Templates" that enforce PDO Prepared Statements.
- **Isolation:** The `builder/` directory is outside the Apache `DocumentRoot`. The internet cannot see your blueprints.
    

## 📜 License

MIT

---

**Deployment Note:**
- **Public Domain:** For launching on a public domain, the recommended approach is to use a server with a working **Traefik** instance and a **Cloudflare Tunnel**.
- **Personal Use:** For personal use only, launch it on a server and connect your devices via **Tailscale**.
