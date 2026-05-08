# Modern PHP Monolith — AI-Second

A self-replicating PHP application factory driven by Claude Code. Describe what you want to build in `SEED.md`, run `/build`, and an orchestrated AI workflow handles the rest — database design, scaffolding, UI, security analysis, and deployment.

---

## The Core Idea

Most AI-assisted dev stacks hand the AI a blank canvas and hope for the best. The result is insecure boilerplate, inconsistent architecture, and a developer left auditing code they didn't write and don't fully trust.

This stack flips the order. The important structural code — security patterns, PDO bindings, session handling, caching, controller/view separation — is **prewritten first** using Jinja2 templates. The AI never invents that layer. It uses the templates as lego blocks via MCP, snapping them together to build features on top of a foundation you already trust.

That's the peace of mind. The AI is second. The structure comes first.

**Other benefits:**

- **~300 tokens** to understand any feature vs. 2,000+ in a Next.js/FastAPI stack
- **No structural code written from scratch** — Jinja2 templates enforce architecture, PDO, and security patterns
- **25 years of PHP training data** — LLMs write correct PHP/SQL on the first attempt, reliably
- **One file = one feature** — controller logic at the top, HTML at the bottom, no client/server state sync

---

## The Workflow

### `/build` — Build from a prompt

Fill `SEED.md` with your app idea. Then:

```
/build
```

The workflow runs automatically:

1. Reads `SEED.md` + validates `.env`
2. Brainstorming session (you approve the scope)
3. Generates a feature-by-feature build plan
4. Spins up a git worktree for the feature branch
5. Builds each feature using the Golden Recipe (DB → scaffold → UI → CSS)
6. Runs SAST security analysis on all generated code
7. Fixes any findings, then reports for review

### `/launch` — Deploy to the internet

After reviewing the running app on localhost:

```
/launch
```

Adds a Cloudflare tunnel container to your stack, rebuilds, and generates a knowledge graph of the codebase.

---

## The Stack

| Layer | Technology |
|-------|-----------|
| Web server | Nginx (reverse proxy, separate container) |
| Runtime | PHP 8.2-FPM + OPcache |
| Cache / Sessions | Redis 7 (query cache, fragment cache, session store) |
| Database | MySQL 8.0 (PDO prepared statements only, auto-tuned InnoDB buffer) |
| Frontend | HTMX + Alpine.js + Tailwind CSS |
| Code Generator | Python + FastMCP + Jinja2 (inside the container) |
| Admin | phpMyAdmin |
| Deployment | Cloudflare Tunnel (zero port forwarding) |

**No Node.js. No build steps. No hydration. No CORS. Write a file, Nginx + PHP-FPM serves it.**

---

## The Tool Chain

The `/build` workflow orchestrates a set of specialized tools:

| Tool | Role |
|------|------|
| **superpowers** (Claude Code plugin) | Brainstorming, planning, worktrees, subagent execution |
| **php-monolith-builder** (MCP, runs in Docker) | `execute_sql`, `scaffold_crud`, `create_model`, `create_page`, `add_htmx_form`, `build_css`, and more |
| **uncodixify** (skill) | Enforces Linear/Stripe/GitHub design standard — blocks gradient dashboards, glassmorphism, and other AI UI defaults |
| **graphify** (skill) | Generates a navigable Obsidian knowledge graph of the finished app |
| **Stripe MCP** | Native Stripe integration via `mcp.stripe.com` |
| **context7** (MCP) | Pulls live library documentation into context during development |
| **/security:analyze** (command) | Two-pass SAST scan of all generated PHP — finds XSS, SQLi, path traversal, CSRF, auth bypass |

---

## Architecture

```
ai-first-php-monolith/
├── src/
│   ├── builder/          # AI's workshop — Python MCP server + Jinja2 templates
│   │   ├── mcp_server.py
│   │   └── templates/    # "Golden Templates": enforce security, architecture, naming
│   └── public/           # Live application — PHP served by PHP-FPM
│       ├── classes/
│       │   └── BaseModel.php  # Auto query-cache via Redis (getAll/update/delete)
│       ├── redis.php     # Singleton $redis connection
│       ├── cache.php     # Cache helper (get/set/bust)
│       ├── session.php   # Redis session handler (wired into db.php)
│       └── index.php
├── .claude/
│   ├── commands/
│   │   ├── build.md      # /build orchestrator
│   │   ├── launch.md     # /launch deployer
│   │   └── security/
│   │       └── analyze.md # two-pass SAST
│   └── settings.local.json
├── nginx.conf            # Nginx reverse proxy config
├── opcache.ini           # OPcache tuning (copied into container)
├── mysql.cnf             # InnoDB buffer pool (overwritten at build time)
├── docker-compose.yml
├── SEED.md               # Describe your app here
├── CHECKLIST.md          # Setup checklist
└── install.sh            # One-command machine setup
```

`builder/` sits outside Nginx's document root. The internet cannot access templates or the MCP server — only generated files in `public/` are served.

---

## Getting Started

### Prerequisites

- Docker Engine (running)
- Claude Code — `npm install -g @anthropic-ai/claude-code`

### Setup

Clone the repo and run the installer:

```bash
git clone https://github.com/ChrisEOlsen/ai-first-php-monolith.git my-project
cd my-project
./install.sh
```

`install.sh` handles:
- Installing the graphify and uncodixify Claude Code skills
- Registering the superpowers plugin in `~/.claude/settings.json`
- Registering the php-monolith-builder and Stripe MCP servers
- Copying `env.example` → `.env` if missing
- Running `docker compose up -d --build`

After the script completes:

1. Open Claude Code: `claude`
2. Authenticate the Stripe MCP: `/mcp` → follow the OAuth prompt (one-time)
3. Fill in `SEED.md` and `.env`
4. Run `/build`

See `CHECKLIST.md` for the full step-by-step.

---

## The Golden Recipe

Every feature follows the same sequence — enforced by `CLAUDE.md`:

1. **Database first** — `execute_sql` creates the table with PDO-safe schema
2. **Scaffold** — `scaffold_list`, `scaffold_crud`, or `create_model` + `create_page`
3. **Paint the UI** — `add_htmx_form` + uncodixify design standard
4. **Compile** — `build_css` with Tailwind

The AI fills in the blanks. The templates enforce the structure.

---

## Security

- **SQL injection** — impossible via the generated Models; PDO prepared statements are baked into the Jinja2 template
- **CSRF** — token verification installed in every generated form
- **XSS** — `htmlspecialchars` enforced in templates; `run_linter` catches raw output
- **Auth** — sessions with `HttpOnly`, `Secure`, `SameSite=Lax`; login rate-limited (5 attempts / 15 min)
- **Supply chain** — zero npm dependencies; attack surface limited to PHP and the OS
- **SAST** — `/security:analyze` runs after every `/build` before you see the result

---

## License

MIT
