---
description: Build a new PHP application from SEED.md — full automated workflow from spec to running app
allowed-tools: Bash(cat .env), Bash(cat SEED.md), Bash(docker compose ps), Bash(docker compose up *), Bash(docker compose down *), Bash(docker logs *), Bash(git *), Bash(stripe *), Bash(kill *), Bash(sleep *), Bash(grep *), Bash(rm -f *)
---

## Pre-loaded Context

- **SEED.md contents:** !`cat SEED.md 2>/dev/null || echo "MISSING"`
- **Environment vars:** !`cat .env 2>/dev/null || echo "MISSING"`
- **Docker status:** !`docker compose ps 2>/dev/null`

---

You are running the automated build workflow for this PHP monolith. Follow these steps exactly in order. Do not skip steps. Do not proceed past a STOP condition.

The SEED.md and .env contents are already loaded above — do not re-read them.

---

## Step 1: Validate Pre-loaded Context

Using the pre-loaded context above:

If SEED.md shows `MISSING` or is empty, **STOP**:
> "SEED.md is missing. The template is already in the repo — fill in your project details and run /build again."

Extract and note:
- **App name** (for branch naming)
- **Which integrations are checked** (`[x]`): Payments, AI Features (OpenRouter), Public domain (Cloudflare)

---

## Step 2: Env Var Check

Using the pre-loaded `.env` above:

If `.env` shows `MISSING`, **STOP**:
> "No .env file found. Run: `cp env.example .env` then fill in your values."

Check that each required var is present AND non-empty based on the SEED.md checkboxes:

| Integration checked | Required vars |
|---------------------|--------------|
| `[x] Payments (Stripe)` | `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY` |
| `[x] AI Features (OpenRouter)` | `OPENROUTER_API_KEY` |
| `[x] Public domain (Cloudflare)` | `CLOUDFLARE_TUNNEL_TOKEN` |

Note: `STRIPE_WEBHOOK_SECRET` is generated during Step 6b — do not require it here.

If any required var is missing or empty, **STOP** and list exactly which vars need to be filled in. Do not continue until the developer confirms they have been added.

---

## Step 3: Brainstorming ← HUMAN-IN-THE-LOOP

Use the `superpowers:brainstorming` skill.

Pass the full contents of SEED.md as the starting context with this framing:

> "The developer has filled in SEED.md (contents below). Use this as the starting context. The goal is to clarify any missing specifications or architectural decisions for this PHP monolith app before building. Follow the brainstorming process as normal — spec saved to docs/superpowers/specs/ (this path is gitignored intentionally; specs are local only)."

When the brainstorming skill reaches its "Transition to implementation" step — **stop there and do not execute it**. That transition is handled by this build workflow, not by the brainstorming skill.

Present the completed spec to the developer and wait for explicit approval (e.g. "looks good", "approved", "continue"). Then proceed to Step 4.

---

## Step 4: Implementation Plan

Use the `superpowers:writing-plans` skill.

Input: the approved spec from Step 3. The plan should follow the Golden Recipe from CLAUDE.md (database first → scaffold with MCP tools → paint UI with uncodixify → polish with build_css).

---

## Step 5: Feature Branch

Derive the branch name from the app name in SEED.md:
- Lowercase all characters
- Replace spaces with hyphens
- Prefix with `build/`
- Example: "Task Manager" → `build/task-manager`

Use the `superpowers:using-git-worktrees` skill to set up the isolated worktree on this branch.

---

## Step 6: Implementation

Use the `superpowers:subagent-driven-development` skill to implement the plan from Step 4.

Provide subagents with this mandatory context:

> **Subagent Instructions:**
> - Follow the Golden Recipe in CLAUDE.md for every feature: database first (`execute_sql`), scaffold with php-monolith-builder MCP tools, paint the interface, then polish with `build_css`.
> - Never write raw SQL inside `.php` files. Always use php-monolith-builder MCP tools for scaffolding (`scaffold_list`, `create_model`, `create_page`, `add_htmx_form`, `scaffold_auth`, `scaffold_registration`, `create_internal_api`, `build_css`).
> - Before any frontend or design work, invoke the `uncodixify` skill. Follow its rules exactly.
> - For any external API integrations (Stripe, OpenRouter, Cloudflare, etc.), use the context7 MCP to fetch current documentation before implementing.
> - All tool calls are pre-authorized. Do not stop for permission prompts.

When all tasks are complete, ensure all worktree changes are merged to the feature branch.

---

## Step 6b: Stripe Webhook Registration + Automated Local Testing (only if Stripe checked)

Skip this step if `[x] Payments (Stripe)` was not checked in SEED.md.

Read `APP_DOMAIN` and `APP_PORT` from `.env`.

### 1. Register production webhook

Use the Stripe MCP to register a webhook endpoint:

```
https://[APP_DOMAIN]/api/stripe_webhook.php
```

Register for these events at minimum (add more if the app spec requires):
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `customer.subscription.updated`
- `customer.subscription.deleted`

Save the returned production signing secret — you will write it to `.env` at the end of this step.

### 2. Verify webhook registration via Stripe MCP

Use the Stripe MCP to retrieve the webhook endpoint just created. Confirm:
- URL matches `https://[APP_DOMAIN]/api/stripe_webhook.php`
- All registered events are present in the response

If verification fails, re-register and re-verify before continuing.

### 3. Start local stripe listener

Run in background, capturing output to a temp file:

```bash
stripe listen --forward-to http://localhost:[APP_PORT]/api/stripe_webhook.php > /tmp/stripe_listen.log 2>&1 &
STRIPE_LISTEN_PID=$!
sleep 3
```

Parse the local webhook secret from the log:

```bash
LOCAL_SECRET=$(grep -o 'whsec_[A-Za-z0-9]*' /tmp/stripe_listen.log | head -1)
```

If `LOCAL_SECRET` is empty, the Stripe CLI is not installed or not authenticated. Skip to step 6 and note the skip in the final report.

### 4. Temporarily swap in local secret

Write `LOCAL_SECRET` to `.env` as `STRIPE_WEBHOOK_SECRET` so the running app can verify local test events.

### 5. Fire test events and verify

For each registered event type, run `stripe trigger [event]` and check docker logs for a `200` response from the webhook handler:

```bash
stripe trigger payment_intent.succeeded
sleep 2
docker logs [CONTAINER_NAME] --tail 20 2>&1 | grep "stripe_webhook"
```

Repeat for each event. Record pass/fail per event.

If any event returns non-200 or the handler throws an error, note it in the Step 9 report — do not stop the build, as this may be expected if the app logic isn't fully wired yet.

### 6. Stop listener and restore production secret

```bash
kill $STRIPE_LISTEN_PID 2>/dev/null
rm -f /tmp/stripe_listen.log
```

Write the **production** signing secret from step 1 back to `.env` as `STRIPE_WEBHOOK_SECRET`.

---

## Step 7: Security Analysis

Read the file `.claude/commands/security/analyze.md` and follow its instructions exactly.

Scope: `src/public/` only. Do not analyze `src/builder/`.

The report will be written to `.security/SECURITY_REPORT.md`.

---

## Step 8: Security Fixes

Read `.security/SECURITY_REPORT.md`.

If there are **no** Critical, High, or Medium findings — skip this step entirely and go to Step 9.

If there are Critical, High, or Medium findings:

1. Use `superpowers:writing-plans` to create a targeted security fix plan. Input: the Critical/High/Medium findings from the report only. Skip Low severity findings entirely.

2. Use `superpowers:executing-plans` to implement the fixes. No brainstorming step — implement fixes directly from the plan.

---

## Step 8b: Pre-Completion Verification

Use the `superpowers:verification-before-completion` skill, then answer every question below. Fix anything that is NO before proceeding to Step 9.

### Features
- [ ] Every feature listed in SEED.md is implemented — nothing silently skipped
- [ ] All pages require auth where auth is expected (check `auth_required=True` on protected pages)
- [ ] No placeholder, lorem ipsum, or hardcoded dummy data left in the app

### Architecture (Golden Recipe compliance)
- [ ] Every database table created via `execute_sql` — no schema defined inline in PHP
- [ ] Every Model created via `create_model` or `scaffold_*` — no raw PDO in page files
- [ ] Every form uses HTMX (`hx-post`, `hx-get`) — no custom `fetch()` or `axios` calls
- [ ] `build_css` was run after all HTML edits — Tailwind classes are compiled
- [ ] `run_linter` was run — no PHP syntax errors

### Design
- [ ] `uncodixify` skill was invoked before any UI work — rules were followed
- [ ] No gradient hero sections, glassmorphism, or oversized rounded corners
- [ ] Page titles are set correctly (`<title>App Name — Page Name</title>`)
- [ ] Favicon is present (`/favicon.svg` already exists in the template)
- [ ] App is usable on mobile (no fixed-width containers that break small screens)

### External APIs
- [ ] For every external API used (Stripe, OpenRouter, or any other), Context7 MCP was queried for current documentation before implementing — no guessed API signatures
- [ ] Stripe: webhook handler validates signature with `STRIPE_WEBHOOK_SECRET` before processing any event
- [ ] OpenRouter: model name is read from `.env` or a config constant — not hardcoded

### Environment
- [ ] Every env var the app reads at runtime exists in `env.example` with a comment
- [ ] No secrets, API keys, or passwords hardcoded anywhere in `src/public/`

---

## Step 9: Done

Read `APP_PORT` from `.env` (default to `1234` if not set).

Tell the developer:

> **Build complete.**
>
> App running at: `http://localhost:[APP_PORT]`
> Branch: `build/[app-name]`
> Security report: `.security/SECURITY_REPORT.md`
>
> **Stripe webhooks:** [list each event and PASS/FAIL/SKIPPED]
> Production webhook secret written to `.env`.
>
> Review the app. When satisfied:
> - Run `/launch` to go live on your Cloudflare domain
> - Merge to main when ready: `git checkout main && git merge build/[app-name]`
