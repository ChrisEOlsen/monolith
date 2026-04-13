---
description: Build a new PHP application from SEED.md — full automated workflow from spec to running app
---

You are running the automated build workflow for this PHP monolith. Follow these steps exactly in order. Do not skip steps. Do not proceed past a STOP condition.

---

## Step 1: Read SEED.md

Read the file `SEED.md` at the project root.

If SEED.md does not exist or is completely empty, **STOP** and tell the developer:

> "SEED.md is missing. The template is already in the repo — fill in your project details and run /build again."

Extract and note:
- **App name** (for branch naming)
- **Which integrations are checked** (`[x]`): Payments, AI Features (OpenRouter), Public domain (Cloudflare)

---

## Step 2: Env Var Check

Read the `.env` file. If it does not exist, tell the developer:

> "No .env file found. Run: `cp env.example .env` then fill in your values."

Then **STOP**.

If `.env` exists, check that each required var is present AND non-empty based on the SEED.md checkboxes:

| Integration checked | Required vars |
|---------------------|--------------|
| `[x] Payments (Stripe)` | `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET` |
| `[x] AI Features (OpenRouter)` | `OPENROUTER_API_KEY` |
| `[x] Public domain (Cloudflare)` | `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_TUNNEL_TOKEN`, `CLOUDFLARE_DOMAIN` |

If any required var is missing or empty, **STOP** and list exactly which vars need to be filled in. Do not continue until the developer confirms they have been added.

---

## Step 3: Brainstorming ← HUMAN-IN-THE-LOOP

Use the `superpowers:brainstorming` skill.

Pass the full contents of SEED.md as the starting context with this framing:

> "The developer has filled in SEED.md (contents below). Use this as the starting context. The goal is to clarify any missing specifications or architectural decisions for this PHP monolith app before building. Follow the brainstorming process as normal — spec saved to docs/superpowers/specs/."

Wait for the developer to approve the spec before continuing to Step 4.

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

## Step 9: Done

Read `APP_PORT` from `.env` (default to `1234` if not set).

Tell the developer:

> **Build complete.**
>
> App running at: `http://localhost:[APP_PORT]`
> Branch: `build/[app-name]`
> Security report: `.security/SECURITY_REPORT.md`
>
> Review the app. When satisfied:
> - Run `/launch` to go live on your Cloudflare domain
> - Merge to main when ready: `git checkout main && git merge build/[app-name]`
