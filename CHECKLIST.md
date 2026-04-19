# New Project Checklist

Complete this before running `/build`. One-time machine setup items are marked separately from per-project items.

---

## 🖥️ One-Time Machine Setup

Only do these once per server/machine. Skip if already done.

- [ ] **Claude Code installed** — `npm install -g @anthropic-ai/claude-code`
- [ ] **Docker installed and running**
- [ ] **superpowers plugin installed** in Claude Code
- [ ] **graphify skill installed** in `~/.claude/skills/graphify/`
- [ ] **uncodixify skill installed** in `~/.claude/skills/uncodixify/`
- [ ] **Global `~/.claude/settings.json`** has `php-monolith-builder` and `stripe` MCP servers registered (see README.md)

---

## 📁 Per-Project Setup

Do these every time you start a new project from this repo.

### 1. Clone & Configure

- [ ] Clone repo into a new directory with your project name
  ```bash
  git clone <repo-url> my-project-name
  cd my-project-name
  ```
- [ ] Copy env file
  ```bash
  cp env.example .env
  ```
- [ ] Edit `.env` — at minimum set unique values for:
  - `APP_NAME` — unique name (avoids Docker container collisions if running multiple projects)
  - `APP_PORT` — unique port (e.g. 3000, 4000 — avoid conflicts with other running projects)
  - `DB_PORT` — unique DB port
  - `DB_DATABASE`, `DB_USER`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`

### 2. Fill in SEED.md

- [ ] Open `SEED.md` and fill in:
  - App name and description
  - Feature list (be specific)
  - Check integrations that apply (`Auth`, `Stripe`, `OpenRouter`, `Cloudflare`)
  - Design preferences

### 3. Add Integration Credentials (only for checked integrations)

- [ ] **Stripe** → add `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET` to `.env`
- [ ] **OpenRouter** → add `OPENROUTER_API_KEY` to `.env`
- [ ] **Cloudflare** (needed for `/launch`) → add `CLOUDFLARE_TUNNEL_TOKEN` to `.env` (domain routing configured in CF dashboard, not here)

### 4. Start Docker

- [ ] Start the containers (MCP server runs inside — must be up before Claude Code connects)
  ```bash
  docker compose up -d --build
  ```
- [ ] Verify containers running
  ```bash
  docker compose ps
  ```
  Expected: `app`, `db`, `pma` all showing `Up`

### 5. Open Claude Code

- [ ] Open Claude Code in the project directory
  ```bash
  claude
  ```
- [ ] Verify MCP tools are detected — run `/mcp` and confirm `php-monolith-builder` tools appear
- [ ] If MCP tools missing: ensure Docker container is running first, then restart Claude Code

---

## 🚀 Ready to Build

Once all boxes are checked:

```
/build
```

Claude will read `SEED.md`, verify your `.env`, then start the brainstorming session.

---

## 🌐 Ready to Deploy

After approving the running app on localhost:

```
/launch
```

Requires Cloudflare vars in `.env`.
