---
description: Two-pass security audit on src/public/ — outputs .security/SECURITY_REPORT.md
---

You are running a security analysis on this PHP application. Follow this methodology exactly. Do not skip steps or combine passes.

## Scope

- **Analyze:** `src/public/` — all `.php` files recursively
- **Exclude:** `src/builder/` — this is the MCP server, it is deactivated in production and is not part of the attack surface. Do not report any findings from `src/builder/`.

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

Use Glob to find every `.php` file in `src/public/` recursively. Read each file. For each file, scan for untrusted data entry points:

- `$_GET`, `$_POST`, `$_REQUEST`, `$_FILES`, `$_COOKIE`, `$_SERVER`
- Return values from Model method calls that originate from database rows (e.g. `$model->getAll()`, `$model->find()`, `$model->getById()`)

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

**Auth bypass** — page that should require authentication but does not check `$_SESSION['user_id']` or equivalent session guard at the top of the file

**Command injection** — variable passed to `exec()`, `shell_exec()`, `system()`, `passthru()`
```php
// Vulnerable:
exec("convert " . $_POST['filename']);
```

**Hardcoded secrets** — API keys, passwords, or private tokens written directly in source files (not loaded from `$_ENV` or `getenv()`)
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
