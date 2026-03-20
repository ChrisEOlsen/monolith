# Fix Production Vulnerabilities Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remediate all production-affecting security vulnerabilities identified in the audit.

**Architecture:** We will implement global security settings in `db.php` and `csrf.php` (potentially renaming to `security.php`), update Jinja2 templates to enforce escaping and validation, and port existing rate-limiting logic to the registration flow.

**Tech Stack:** PHP 8.2, Jinja2 (Python), MySQL.

---

### Task 1: Secure Session Configuration & Global Security Utilities

**Files:**
- Modify: `src/public/db.php`
- Modify: `src/public/csrf.php` (Add validation helpers)

- [ ] **Step 1: Update `src/public/db.php` with secure session settings**

```php
<?php
// ... existing code ...

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', // Default to current domain
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// ... existing code ...
```

- [ ] **Step 2: Add `is_internal_url` helper to `src/public/csrf.php` (or a new security.php)**

```php
/**
 * Validate if a URL is internal to prevent Open Redirects.
 */
function is_internal_url($url) {
    if (empty($url)) return false;
    // Must start with / and not contain // (which could be protocol-relative)
    return str_starts_with($url, '/') && !str_starts_with($url, '//');
}
```

- [ ] **Step 3: Verify session flags**
Run a local PHP script or check headers to ensure `HttpOnly` and `SameSite=Lax` are set.

- [ ] **Step 4: Commit**

```bash
git add src/public/db.php src/public/csrf.php
git commit -m "security: enforce secure session cookies and add internal URL helper"
```

---

### Task 2: Fix Open Redirect in Login and Registration

**Files:**
- Modify: `src/public/login.php`
- Modify: `src/builder/templates/login_page.php.j2`
- Modify: `src/builder/templates/register_page.php.j2`

- [ ] **Step 1: Update `login.php` redirect logic**

```php
// ...
        // Redirect to intended destination or home
        $redirect = $_SESSION['auth_redirect_to'] ?? '/index.php';
        if (!is_internal_url($redirect)) {
            $redirect = '/index.php';
        }
        unset($_SESSION['auth_redirect_to']);
        header("Location: " . $redirect);
// ...
```

- [ ] **Step 2: Synchronize templates**
Apply the same logic to `login_page.php.j2` and `register_page.php.j2`.

- [ ] **Step 3: Commit**

```bash
git add src/public/login.php src/builder/templates/login_page.php.j2 src/builder/templates/register_page.php.j2
git commit -m "security: fix open redirect vulnerability in auth flows"
```

---

### Task 3: Secure Templates against XSS and SQL Injection

**Files:**
- Modify: `src/builder/templates/pdo_class.php.j2`
- Modify: `src/builder/templates/empty_page.php.j2`
- Modify: `src/builder/templates/htmx_form.php.j2`
- Modify: `src/builder/templates/list_page.php.j2`

- [ ] **Step 1: Secure `pdo_class.php.j2` (SQLi)**
Use backticks for table names and validate keys in `update`.

- [ ] **Step 2: Secure UI templates (XSS)**
Ensure all `{{ variable }}` tags use appropriate escaping if they contain user input, or ensure the generator sanitizes them. Actually, Jinja2's default is often escaping, but we should be explicit or use PHP's `htmlspecialchars` in the generated code.

- [ ] **Step 3: Commit**

```bash
git add src/builder/templates/
git commit -m "security: harden templates against SQLi and XSS"
```

---

### Task 4: Fix LFI in Template Generation

**Files:**
- Modify: `src/builder/mcp_server.py`

- [ ] **Step 1: Implement strict validation for model and filenames**
Add regex checks to ensure `name` and `filename` only contain `[a-zA-Z0-9_/.]` and no `..`.

- [ ] **Step 2: Commit**

```bash
git add src/builder/mcp_server.py
git commit -m "security: prevent LFI and path traversal in MCP builder tools"
```

---

### Task 5: Implement Rate Limiting for Registration

**Files:**
- Modify: `src/builder/templates/register_page.php.j2`

- [ ] **Step 1: Port rate-limiting logic from `login.php` to registration template**

- [ ] **Step 2: Commit**

```bash
git add src/builder/templates/register_page.php.j2
git commit -m "security: add rate limiting to registration endpoint"
```
