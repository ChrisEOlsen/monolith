# Automated Build Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire superpowers, php-monolith-builder MCP, uncodixify, graphify, Stripe MCP, Cloudflare MCP, and a custom security SAST skill into a single `/build` + `/launch` automated workflow triggered from `SEED.md`.

**Architecture:** Claude Code command files (`.claude/commands/*.md`) act as the orchestration layer — each file is a rich prompt that tells Claude which existing skills and MCP tools to invoke, in what order, with what context. No existing tools are reimplemented. The security skill is the only net-new logic, adapted from the gemini-cli-extensions/security TOML methodology into a native Claude Code command file.

**Tech Stack:** Claude Code slash commands, superpowers plugin (brainstorming / writing-plans / using-git-worktrees / subagent-driven-development / executing-plans), php-monolith-builder MCP, uncodixify skill, graphify skill, context7 MCP, Stripe MCP, `@cloudflare/mcp-server-cloudflare` MCP

---

## File Map

| File | Action | Responsibility |
|------|--------|----------------|
| `.claude/settings.local.json` | Modify | Add Cloudflare MCP + broad auto-permissions; keep existing permissions |
| `.claude/commands/build.md` | Create | `/build` orchestrator — full 9-step workflow |
| `.claude/commands/launch.md` | Create | `/launch` — Cloudflare deploy + graphify |
| `.claude/commands/security/analyze.md` | Create | `/security:analyze` — two-pass SAST on `src/public/` |
| `SEED.md` | Create | Project spec template for developer to fill in |
| `env.example` | Modify | Append Stripe, OpenRouter, Cloudflare vars |
| `CLAUDE.md` | Modify | Remove MCP section; replace inline Uncodixify rules with skill reference; add workflow note |

---

## Task 1: Update `.claude/settings.local.json`

Merge the existing permissions with the new Cloudflare MCP registration and broad auto-permissions for automated workflow runs.

**Files:**
- Modify: `.claude/settings.local.json`

- [ ] **Step 1: Read the current file**

```bash
cat .claude/settings.local.json
```

Expected output: existing JSON with a few `Read()` and `Bash()` permissions.

- [ ] **Step 2: Replace the file with the merged config**

Write `.claude/settings.local.json`:

```json
{
  "permissions": {
    "allow": [
      "Read(//Users/crispychris/.claude/**)",
      "Read(//Users/crispychris/.claude/plugins/**)",
      "Read(//Users/crispychris/.claude/skills/**)",
      "Bash(python3 -m json.tool)",
      "Bash(gh api:*)",
      "Bash(*)",
      "Edit(*)",
      "Write(*)",
      "Glob(*)",
      "Grep(*)",
      "mcp__*"
    ]
  },
  "mcpServers": {
    "cloudflare": {
      "command": "npx",
      "args": ["-y", "@cloudflare/mcp-server-cloudflare"],
      "env": {
        "CLOUDFLARE_API_TOKEN": "${CLOUDFLARE_API_TOKEN}"
      }
    }
  }
}
```

- [ ] **Step 3: Validate JSON is well-formed**

```bash
python3 -m json.tool .claude/settings.local.json
```

Expected: JSON pretty-prints without errors.

- [ ] **Step 4: Commit**

```bash
git add .claude/settings.local.json
git commit -m "config: add Cloudflare MCP and broad auto-permissions for automated workflow"
```

---

## Task 2: Create `SEED.md` Template

This file lives at the project root. Developers fill it in when starting a new project. The `/build` command reads its checkboxes to determine which env vars to validate.

**Files:**
- Create: `SEED.md`

- [ ] **Step 1: Create the file**

Write `SEED.md`:

```markdown
# SEED.md — Project Specification

## App Identity
- **Name:** 
- **Description:** (1-2 sentences — what does this app do and for whom?)

## Features
<!-- List each feature as a bullet. Be specific. -->
- 

## Integrations
<!-- Check all that apply by filling in details -->
- [ ] **Auth** — user login/registration required
- [ ] **Payments (Stripe)** — describe what's being sold:
- [ ] **AI Features (OpenRouter)** — describe what the AI does:
- [ ] **Public domain (Cloudflare)** — domain name:

## Design Preferences
- **Theme:** (dark / light / no preference)
- **Color palette:** (pick from the Uncodixify palette list in CLAUDE.md, or leave blank for Claude to choose)
- **Vibe:** (e.g. "clean SaaS", "minimal tool", "dashboard-heavy")

## Notes
<!-- Anything else the LLM should know before brainstorming -->
```

- [ ] **Step 2: Verify the file exists**

```bash
cat SEED.md
```

Expected: the template content above.

- [ ] **Step 3: Commit**

```bash
git add SEED.md
git commit -m "feat: add SEED.md project spec template"
```

---

## Task 3: Extend `env.example`

Append new variable groups for Stripe, OpenRouter, and Cloudflare. Existing variables are untouched.

**Files:**
- Modify: `env.example`

- [ ] **Step 1: Read the current file to find the last line**

```bash
cat env.example
```

Note the last line of the existing content.

- [ ] **Step 2: Append the new variable groups**

Add to the end of `env.example`:

```dotenv

# AI Features (required if OpenRouter checked in SEED.md)
OPENROUTER_API_KEY=

# Payments (required if Stripe checked in SEED.md)
STRIPE_SECRET_KEY=
STRIPE_PUBLISHABLE_KEY=
STRIPE_WEBHOOK_SECRET=

# Deployment (required for /launch)
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_TUNNEL_TOKEN=
CLOUDFLARE_DOMAIN=
```

- [ ] **Step 3: Verify the full file looks correct**

```bash
cat env.example
```

Expected: all original vars intact, three new sections at the bottom.

- [ ] **Step 4: Commit**

```bash
git add env.example
git commit -m "config: add Stripe, OpenRouter, and Cloudflare vars to env.example"
```

---

## Task 4: Update `CLAUDE.md`

Three targeted edits only. Everything else stays exactly as-is.

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Read the current CLAUDE.md**

```bash
cat CLAUDE.md
```

Identify the exact text of: (a) the MCP Registration section, (b) the Uncodixify styling section header.

- [ ] **Step 2: Add workflow reference at the very top**

Insert this as the first line of `CLAUDE.md`, before everything else:

```markdown
> **Automated Workflow:** This project uses `/build` to build from `SEED.md` and `/launch` to deploy. Run `/build` to start.

```

- [ ] **Step 3: Remove the MCP Registration section**

Delete the entire `## 🤖 MCP Registration` section and its code block. This config now lives in `.claude/settings.local.json`.

- [ ] **Step 4: Replace the inline Uncodixify rules**

Find the `# 🎨 Uncodixify - Styling Guidelines` section (everything from that heading to the end of the color palette tables). Replace the entire section body with:

```markdown
# 🎨 Frontend Design

For all frontend and UI work, invoke the `uncodixify` skill. It contains the full design system: layout rules, banned patterns, color palettes, and typography standards.
```

- [ ] **Step 5: Verify the file reads cleanly**

```bash
cat CLAUDE.md
```

Expected: workflow note at top, MCP section gone, Uncodixify section replaced with one-liner skill reference.

- [ ] **Step 6: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: trim CLAUDE.md — remove MCP config, shrink Uncodixify section, add workflow note"
```

---

## Task 5: Create `/security:analyze` Command

The two-pass SAST skill adapted from gemini-cli-extensions/security. Can be run standalone or from `/build`.

**Files:**
- Create: `.claude/commands/security/analyze.md`

- [ ] **Step 1: Create the directory**

```bash
mkdir -p .claude/commands/security
```

- [ ] **Step 2: Create the command file**

Write `.claude/commands/security/analyze.md`:

```markdown
---
description: Two-pass security audit on src/public/ — outputs .security/SECURITY_REPORT.md
---

You are running a security analysis on this PHP application. Follow this methodology exactly. Do not skip steps or combine passes.

## Scope

- **Analyze:** `src/public/` — all `.php` files recursively
- **Exclude:** `src/builder/` — this is the MCP server, it is deactivated in production and is not part of the attack surface. Do not report any findings from builder/.

## Setup

Create `.security/` directory if it does not exist.

Create `.security/SECURITY_REPORT.md` with this header:

```
# Security Analysis Report
Date: [today's date]
Scope: src/public/
Excluded: src/builder/ (MCP server — not production attack surface)
```

## Pass 1 — Reconnaissance

Read every `.php` file in `src/public/` (use Glob to find them all). For each file, scan for untrusted data entry points:

- `$_GET`, `$_POST`, `$_REQUEST`, `$_FILES`, `$_COOKIE`, `$_SERVER`
- Return values from Model method calls that originate from database rows (e.g. `$model->getAll()`, `$model->find()`)

For each entry point found, make a note: `FILE:LINE — $variable — context`. Do NOT do deep analysis yet. Complete the full scan of all files before moving to Pass 2.

## Pass 2 — Investigation

For each entry point flagged in Pass 1, trace the variable through the code to find where it is used. Check for these sink types:

**XSS** — variable echoed/printed to HTML without `htmlspecialchars()` or `htmlentities()`
```php
// Vulnerable:
echo $_GET['name'];
echo "<h1>" . $user['name'] . "</h1>";

// Safe:
echo htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8');
```

**SQL Injection** — variable used in a raw query string via concatenation (PDO prepared statements are safe — only flag raw concatenation reaching `$pdo->query()` or `$pdo->exec()`)
```php
// Vulnerable:
$pdo->query("SELECT * FROM users WHERE id = " . $_GET['id']);

// Safe (PDO prepared statement):
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
```

**Path Traversal / LFI** — variable used in `include`, `require`, `file_get_contents`, `fopen`, `readfile`
```php
// Vulnerable:
include($_GET['page'] . '.php');
```

**CSRF** — POST endpoint that changes state but does not call `verify_csrf_token()` from `csrf.php`
```php
// Vulnerable: POST handler with no CSRF check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // process form — no verify_csrf_token() call
}
```

**Auth bypass** — page that should require authentication but does not check `$_SESSION['user_id']` or equivalent session guard
```php
// Vulnerable:
// No session check at top of protected page
```

**Command injection** — variable passed to `exec()`, `shell_exec()`, `system()`, `passthru()`
```php
// Vulnerable:
exec("convert " . $_POST['filename']);
```

**Hardcoded secrets** — API keys, passwords, or private tokens written directly in source files (not loaded from environment)
```php
// Vulnerable:
$stripe_key = "sk_live_abc123...";
```

## False-Positive Check

Before writing any finding to the report, verify ALL five criteria are true:

1. Is the code present in executable, non-commented production code?
2. Can you identify the exact file and line number?
3. Is this based on direct evidence from reading the code (not speculation about how a framework might behave)?
4. Can it be fixed through a code modification?
5. Would this have a plausible security impact in a real production environment?

If ANY criterion is false, do not report the finding.

## Report Format

For each confirmed finding, append to `.security/SECURITY_REPORT.md`:

```
---
VULN-[N]
Name: [descriptive name, e.g. "Reflected XSS in search parameter"]
Severity: Critical | High | Medium
File: src/public/[filename.php]:[line number]
Description: [what the vulnerability is and how it could be exploited — 2-3 sentences]
Recommendation: [specific code change to fix it — include the corrected code snippet]
```

**Severity Guide:**

| Level | Criteria |
|-------|----------|
| Critical | RCE, full authentication bypass, SQL injection with data exfiltration potential |
| High | Stored/reflected XSS, path traversal, CSRF on sensitive state-changing actions |
| Medium | DOM-based XSS in limited context, missing auth on low-sensitivity page, CSRF on minor action |
| Low | **Omit entirely — do not include Low findings in the report** |

## Completion

After completing both passes, append to `.security/SECURITY_REPORT.md`:

```
---
Analysis complete.
Findings: [N total — X critical, X high, X medium]
```

Report the summary to the user and confirm the full report is at `.security/SECURITY_REPORT.md`.
```

- [ ] **Step 3: Verify the file was created**

```bash
cat .claude/commands/security/analyze.md | head -5
```

Expected: the frontmatter `---` and `description:` line.

- [ ] **Step 4: Commit**

```bash
git add .claude/commands/security/
git commit -m "feat: add /security:analyze command — two-pass SAST for src/public/"
```

---

## Task 6: Create `/build` Command

The main orchestrator. Reads SEED.md, checks env, then invokes superpowers skills in sequence.

**Files:**
- Create: `.claude/commands/build.md`

- [ ] **Step 1: Create the command file**

Write `.claude/commands/build.md`:

```markdown
---
description: Build a new PHP application from SEED.md — full automated workflow from spec to running app
---

You are running the automated build workflow for this PHP monolith. Follow these steps exactly in order. Do not skip steps. Do not proceed past a STOP condition.

---

## Step 1: Read SEED.md

Read the file `SEED.md` at the project root.

If SEED.md does not exist or is completely empty, **STOP** and tell the developer:

> "SEED.md is missing. Copy the template (it should already be in the repo) and fill in your project details before running /build."

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

Provide the full contents of SEED.md as the starting context. Tell the brainstorming skill:

> "The developer has filled in SEED.md (contents below). Use this as the starting context for brainstorming. The goal is to clarify missing specifications and architectural decisions for this PHP monolith app before building. The spec should be saved to docs/superpowers/specs/ as usual."

[paste SEED.md contents here]

Wait for the developer to approve the spec before continuing to Step 4.

---

## Step 4: Implementation Plan

Use the `superpowers:writing-plans` skill.

Provide the approved spec from Step 3 as input. The plan should follow the Golden Recipe in CLAUDE.md (database first → scaffold → paint UI → polish).

---

## Step 5: Feature Branch

Derive the branch name from the app name in SEED.md:
- Lowercase
- Spaces → hyphens
- Prefix with `build/`
- Example: "Task Manager" → `build/task-manager`

Use the `superpowers:using-git-worktrees` skill to set up the worktree on this branch.

---

## Step 6: Implementation

Use the `superpowers:subagent-driven-development` skill to implement the plan from Step 4.

Give subagents this mandatory context:

> **Subagent Instructions:**
> - Follow the Golden Recipe in CLAUDE.md for every feature: database first, then scaffold with php-monolith-builder MCP tools, then paint the interface, then polish.
> - Never write raw SQL in PHP files. Always use php-monolith-builder MCP tools (`execute_sql`, `scaffold_list`, `create_model`, `create_page`, `add_htmx_form`, `scaffold_auth`, `build_css`, etc.) for all scaffolding.
> - Invoke the `uncodixify` skill before making any frontend or design decisions.
> - Use the context7 MCP for any external API integrations (Stripe, OpenRouter, etc.) to get current documentation.
> - All tool calls are pre-authorized — do not stop for permission prompts.

When all tasks are complete, ensure all worktree changes are merged to the feature branch.

---

## Step 7: Security Analysis

Read the file `.claude/commands/security/analyze.md` and follow its instructions exactly to run the security analysis.

Scope: `src/public/` only. Do not analyze `src/builder/`.

The report will be written to `.security/SECURITY_REPORT.md`.

---

## Step 8: Security Fixes

Read `.security/SECURITY_REPORT.md`.

If there are **no** Critical, High, or Medium findings — skip this step entirely.

If there are Critical, High, or Medium findings:

1. Use `superpowers:writing-plans` to create a targeted security fix plan. The input is the list of Critical/High/Medium findings from the report. Skip Low severity findings entirely.

2. Use `superpowers:executing-plans` to implement the fixes.

Do not run brainstorming for this step — implement fixes directly from the report.

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
> - Merge to main: `git checkout main && git merge build/[app-name]`
```

- [ ] **Step 2: Verify the file was created**

```bash
cat .claude/commands/build.md | head -5
```

Expected: the frontmatter `---` and `description:` line.

- [ ] **Step 3: Commit**

```bash
git add .claude/commands/build.md
git commit -m "feat: add /build orchestrator command"
```

---

## Task 7: Create `/launch` Command

Deploy to Cloudflare and generate the knowledge graph.

**Files:**
- Create: `.claude/commands/launch.md`

- [ ] **Step 1: Create the command file**

Write `.claude/commands/launch.md`:

```markdown
---
description: Deploy the app live via Cloudflare tunnel and generate an Obsidian knowledge graph
---

You are running the deployment workflow. This should only be run after the developer has reviewed and approved the running app from `/build`.

---

## Step 1: Check Prerequisites

Read `.env` and verify these vars exist and are non-empty:

- `CLOUDFLARE_API_TOKEN`
- `CLOUDFLARE_TUNNEL_TOKEN`
- `CLOUDFLARE_DOMAIN`

If any are missing, **STOP** and tell the developer:

> "The following vars are missing from .env: [list]. Add them and run /launch again."

---

## Step 2: Cloudflare Tunnel

Use the Cloudflare MCP server tools to:

1. Create or configure a tunnel using `CLOUDFLARE_TUNNEL_TOKEN`
2. Configure routing so that `CLOUDFLARE_DOMAIN` points to the tunnel → local app

---

## Step 3: Rebuild Containers

Run:

```bash
docker compose up -d --build
```

Wait for containers to come up cleanly before continuing.

---

## Step 4: Knowledge Graph

Use the `graphify` skill on `src/public/`.

This generates a navigable knowledge graph of the application codebase, suitable for viewing in Obsidian.

---

## Step 5: Report

Tell the developer:

> **Deployment complete.**
>
> Live at: `https://[CLOUDFLARE_DOMAIN]`
> Knowledge graph: `graphify-out/`
>
> Open `graphify-out/index.html` in a browser or import the JSON into Obsidian.
```

- [ ] **Step 2: Verify the file was created**

```bash
cat .claude/commands/launch.md | head -5
```

Expected: frontmatter with `description:` line.

- [ ] **Step 3: Commit**

```bash
git add .claude/commands/launch.md
git commit -m "feat: add /launch deploy command"
```

---

## Task 8: Final Verification

Verify the full file structure is in place and all JSON is valid.

**Files:**
- Read: all new/modified files

- [ ] **Step 1: Verify directory structure**

```bash
find .claude/commands -type f | sort
```

Expected output:
```
.claude/commands/build.md
.claude/commands/launch.md
.claude/commands/security/analyze.md
```

- [ ] **Step 2: Validate settings.local.json**

```bash
python3 -m json.tool .claude/settings.local.json
```

Expected: valid JSON, no errors.

- [ ] **Step 3: Verify SEED.md exists at project root**

```bash
ls -la SEED.md
```

Expected: file exists.

- [ ] **Step 4: Verify env.example has the new vars**

```bash
grep -E "OPENROUTER|STRIPE|CLOUDFLARE" env.example
```

Expected: all six new vars appear.

- [ ] **Step 5: Verify CLAUDE.md has the workflow note and no MCP section**

```bash
head -3 CLAUDE.md
```

Expected: workflow reference on the first or second line.

```bash
grep -n "MCP Registration" CLAUDE.md
```

Expected: no output (section removed).

- [ ] **Step 6: Final commit**

```bash
git add -A
git status
```

Confirm only expected files are staged. Then:

```bash
git commit -m "feat: complete automated build workflow — /build, /launch, /security:analyze"
```

---

## Self-Review

**Spec coverage check:**

| Spec requirement | Task |
|-----------------|------|
| settings.local.json with Cloudflare MCP + broad permissions | Task 1 |
| SEED.md template | Task 2 |
| env.example extended | Task 3 |
| CLAUDE.md trimmed (3 edits) | Task 4 |
| /security:analyze two-pass SAST | Task 5 |
| /build orchestrator (9 steps) | Task 6 |
| /launch Cloudflare + graphify | Task 7 |
| Verification | Task 8 |

All spec requirements covered. No TBDs. No placeholders.
