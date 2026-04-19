# New Project Checklist
---

## 🖥️ One-Time Machine Setup

Only do these once per server/machine. Skip if already done.

- [ ] **Claude Code installed** — `npm install -g @anthropic-ai/claude-code`
- [ ] **Docker installed and running**
- [ ] **Stripe CLI installed** — [stripe.com/docs/stripe-cli](https://stripe.com/docs/stripe-cli) — needed for local webhook testing (`stripe listen`)
- [ ] **Authenticate Stripe MCP** — open Claude Code, run `/mcp`, find the `stripe` server and follow the OAuth login prompt in your browser (one-time auth, persists across sessions)

---

## 📁 Per-Project Setup

Do these every time you start a new project from this repo.

### 1. Clone & Configure `.env`

- [ ] Clone repo into a new directory with your project name
  ```bash
  git clone <repo-url> my-project-name
  cd my-project-name
  ```
- [ ] Copy and fill in `.env` — **do this before running the install script**
  ```bash
  cp env.example .env
  ```
  Set all values that apply:
  - `APP_NAME` — unique name, determines Docker container name and MCP config
  - `APP_PORT`, `DB_PORT` — unique ports (avoid conflicts if running multiple projects)
  - `DB_DATABASE`, `DB_USER`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`
  - `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY` — if using Stripe (`STRIPE_WEBHOOK_SECRET` generated during `/build`)
  - `OPENROUTER_API_KEY` — if using OpenRouter
  - `CLOUDFLARE_TUNNEL_TOKEN`, `APP_DOMAIN` — if deploying via `/launch`

### 2. Run the Install Script

- [ ] Run from the project root — installs skills, configures MCP, starts Docker:
  ```bash
  ./install.sh
  ```
  Safe to rerun. Reads `APP_NAME` from `.env` to generate `.mcp.json`.

### 3. Fill in SEED.md

- [ ] Open `SEED.md` and fill in:
  - App name and description
  - Feature list (be specific)
  - Check integrations that apply (`Auth`, `Stripe`, `OpenRouter`, `Cloudflare`)
  - Design preferences

### 4. Open Claude Code

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

If Stripe is enabled, `/build` will register the production webhook and pause to give you the `STRIPE_WEBHOOK_SECRET`. Add it to `.env` when prompted.

---

## 💳 Testing Stripe Locally

After `/build` completes, test the full payment flow locally using the Stripe CLI:

```bash
stripe listen --forward-to localhost:[APP_PORT]/api/stripe_webhook.php
```

This prints a **local** webhook secret (`whsec_...`). Temporarily swap it into `.env`:

```
STRIPE_WEBHOOK_SECRET=whsec_...(local one from stripe listen)
```

Trigger test events in a second terminal:

```bash
stripe trigger payment_intent.succeeded
```

When done testing locally, restore the **production** webhook secret (from `/build`) before running `/launch`.

---

## 🌐 Ready to Deploy

After approving the running app on localhost:

```
/launch
```

Requires Cloudflare vars in `.env`.
