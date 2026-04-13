# Automated Build Workflow — Design Spec
**Date:** 2026-04-13  
**Status:** Approved

---

## Overview

Transform this PHP monolith template into a fully automated, AI-driven project factory. A developer fills in `SEED.md`, runs `/build`, answers brainstorming questions, then walks away. Claude orchestrates the entire pipeline — scaffolding, styling, security — and hands back a running app on localhost. When satisfied, the developer runs `/launch` to go live.

All orchestration uses existing tools (superpowers plugin, php-monolith-builder MCP, graphify skill, uncodixify skill, context7 MCP, Stripe MCP, Cloudflare MCP) wired together via Claude Code command files. No reimplementation of existing tools.

---

## Deliverables

| File | Purpose |
|------|---------|
| `.claude/settings.local.json` | Cloudflare MCP registration + broad auto-permissions |
| `.claude/commands/build.md` | `/build` orchestrator command |
| `.claude/commands/launch.md` | `/launch` deploy + graphify command |
| `.claude/commands/security/analyze.md` | `/security:analyze` two-pass SAST |
| `SEED.md` | Project spec template (developer fills in per project) |
| `env.example` | Extended with Stripe, OpenRouter, Cloudflare vars |
| `CLAUDE.md` | Trimmed: remove MCP registration, shrink Uncodixify section, add workflow reference |

---

## 1. Settings — `.claude/settings.local.json`

Registers the Cloudflare MCP server at project scope and pre-authorizes all tool calls so subagents never prompt during automated runs.

```json
{
  "permissions": {
    "allow": [
      "Bash(*)",
      "Read(*)",
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

php-monolith-builder and Stripe MCP stay in global `~/.claude/settings.json` — no change needed there.

---

## 2. `SEED.md` Template

Lives at project root. Developer fills this in before running `/build`. The `/build` command reads the checkboxes to determine which env vars to validate.

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
- **Color palette:** (pick from CLAUDE.md list, or leave blank for Claude to choose)
- **Vibe:** (e.g. "clean SaaS", "minimal tool", "dashboard-heavy")

## Notes
<!-- Anything else the LLM should know before brainstorming -->
```

---

## 3. `env.example` Additions

Appended to existing vars (existing vars unchanged):

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

---

## 4. `/build` Workflow — `commands/build.md`

### Trigger
Developer runs `/build` in Claude Code after filling in `SEED.md`.

### Sequence

```
Step 1 — Read SEED.md
  Fail immediately if SEED.md is missing or empty.

Step 2 — Env var check
  Parse SEED.md checkboxes:
    [x] Payments   → require STRIPE_SECRET_KEY, STRIPE_PUBLISHABLE_KEY, STRIPE_WEBHOOK_SECRET
    [x] OpenRouter → require OPENROUTER_API_KEY
    [x] Cloudflare → require CLOUDFLARE_API_TOKEN, CLOUDFLARE_TUNNEL_TOKEN, CLOUDFLARE_DOMAIN
  Check .env file for each required var.
  If any missing: STOP. List exactly which vars are missing. Do not proceed.

Step 3 — superpowers:brainstorming  ← HUMAN-IN-THE-LOOP
  Pass SEED.md content as starting context.
  Brainstorming fills gaps in spec, clarifies architecture decisions.
  Spec written to docs/superpowers/specs/.
  Developer approves spec before workflow continues.

Step 4 — superpowers:writing-plans
  Input: approved spec from step 3.
  Output: implementation plan.

Step 5 — Create feature branch
  Branch name: build/<app-name> (app name from SEED.md, lowercased, hyphenated)
  Use superpowers:using-git-worktrees for isolation.

Step 6 — superpowers:subagent-driven-development
  Subagents follow the Golden Recipe in CLAUDE.md:
    - execute_sql for schema
    - php-monolith-builder MCP tools for scaffolding
    - uncodixify skill for all frontend/design work
    - context7 MCP for any external API integrations
  Worktrees auto-merge to build/<app-name> when each task is done.
  No permission prompts — settings.local.json pre-authorizes all tool calls.

Step 7 — /security:analyze
  Runs automatically after implementation completes.
  Scope: src/public/ only. src/builder/ explicitly excluded.
  Output: .security/SECURITY_REPORT.md

Step 8 — Security fixes
  Read .security/SECURITY_REPORT.md.
  Use superpowers:writing-plans to produce a targeted fix plan from Critical/High/Medium findings.
  Then use superpowers:executing-plans to implement the fixes.
  Skip Low findings entirely.
  No brainstorming step.

Step 9 — Notify developer
  "Build complete.
   App running at: http://localhost:${APP_PORT}
   Branch: build/<app-name>
   Security report: .security/SECURITY_REPORT.md
   When satisfied, run /launch to go live."
```

### Human-in-the-loop
Only one pause point: step 3 (brainstorming). After the spec is approved, the workflow runs autonomously through to step 9.

---

## 5. `/security:analyze` — `commands/security/analyze.md`

Adapted from [gemini-cli-extensions/security](https://github.com/gemini-cli-extensions/security). Can be run standalone at any time, not just from `/build`.

### Scope
- Analyze: `src/public/` (all `.php` files)
- Exclude: `src/builder/` (MCP server — deactivated in production, not part of attack surface)

### Methodology — Two-Pass SAST

**Pass 1 — Reconnaissance**  
Scan all in-scope files. Identify all untrusted data entry points:
- `$_GET`, `$_POST`, `$_REQUEST`, `$_FILES`, `$_COOKIE`, `$_SERVER`
- Any data returned from Model methods (database output)

Flag each source as an investigation task without deep analysis.

**Pass 2 — Investigation**  
For each flagged source, trace the variable through the codebase to its sink:
- SQL contexts (should never occur — models use PDO, but verify)
- Output sinks: `echo`, `print`, `<?=`
- Header/redirect sinks: `header()`
- File operation sinks: `file_get_contents`, `include`, `require`
- Execution sinks: `exec`, `shell_exec`, `system`

Confirm a vulnerability only when: (a) sanitization gap is proven by direct evidence, and (b) exploitation is plausible in production.

**Categories assessed:**
1. XSS — unsanitized output to HTML
2. SQL Injection — raw user input in queries (should be caught by PDO templates, but verify)
3. Path Traversal / LFI — user input in file operations
4. CSRF — state-changing endpoints missing CSRF token check
5. Auth bypass — protected pages missing session check
6. Command injection — user input reaching exec/shell functions
7. Hardcoded secrets — API keys or credentials in source files

### Output: `.security/SECURITY_REPORT.md`

Each finding:
```
VULN-001
Name: [vulnerability name]
Severity: Critical | High | Medium
File: src/public/foo.php:42
Description: [what the vulnerability is]
Recommendation: [specific fix]
```

Only Critical, High, and Medium reported. Low severity findings omitted.

### False-positive criteria
Before reporting any finding, verify all five:
1. Present in executable, non-test code?
2. Specific line(s) identifiable?
3. Based on direct evidence, not speculation?
4. Fixable through code modification?
5. Plausible security impact in production?

If any criterion fails, do not report.

---

## 6. `/launch` — `commands/launch.md`

Developer runs this manually after approving the running app.

### Sequence

```
Step 1 — Read .env
  Load CLOUDFLARE_API_TOKEN, CLOUDFLARE_TUNNEL_TOKEN, CLOUDFLARE_DOMAIN.
  Fail if any missing — prompt developer to fill in .env first.

Step 2 — Cloudflare MCP
  Create tunnel using CLOUDFLARE_TUNNEL_TOKEN.
  Assign CLOUDFLARE_DOMAIN to tunnel.

Step 3 — Rebuild containers
  docker compose up -d --build

Step 4 — graphify
  Invoke graphify skill on src/public/
  Generates knowledge graph for Obsidian.

Step 5 — Report
  "Live at https://${CLOUDFLARE_DOMAIN}
   Knowledge graph: graphify-out/"
```

---

## 7. `CLAUDE.md` Changes

Three changes only — everything else preserved:

1. **Remove** the MCP Registration section (config moves to `settings.local.json`)
2. **Replace** the inline Uncodixify styling rules with: *"For all frontend work, invoke the `uncodixify` skill."*
3. **Add** at the top: *"This project uses an automated build workflow. Run `/build` to start from `SEED.md`."*

---

## Tool Wiring Summary

| Tool | Where invoked | How |
|------|--------------|-----|
| superpowers:brainstorming | `/build` step 3 | `Skill("superpowers:brainstorming")` |
| superpowers:writing-plans | `/build` step 4 | `Skill("superpowers:writing-plans")` |
| superpowers:using-git-worktrees | `/build` step 5 | `Skill("superpowers:using-git-worktrees")` |
| superpowers:subagent-driven-development | `/build` step 6 | `Skill("superpowers:subagent-driven-development")` |
| superpowers:writing-plans | `/build` step 8 | `Skill("superpowers:writing-plans")` (fix plan from security report) |
| superpowers:executing-plans | `/build` step 8 | `Skill("superpowers:executing-plans")` |
| php-monolith-builder MCP | Inside subagents | Available as MCP tools automatically |
| uncodixify | Inside subagents | `Skill("uncodixify")` |
| context7 MCP | Inside subagents | Available as MCP tools automatically |
| Stripe MCP | Inside subagents | Available as MCP tools automatically |
| /security:analyze | `/build` step 7 | Direct invocation |
| Cloudflare MCP | `/launch` step 2 | Available as MCP tools via settings.local.json |
| graphify | `/launch` step 4 | `Skill("graphify")` |

---

## Out of Scope

- Modifying `src/builder/` templates or `mcp_server.py` — untouched
- Gemini CLI dependency — replaced entirely by native Claude Code skill
- Any Node.js/npm dependencies — none introduced
