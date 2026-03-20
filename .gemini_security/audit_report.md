# Security Audit Report: AI-First PHP Monolith Template

**Date:** Friday, March 20, 2026
**Auditor:** Gemini CLI (Senior Security Engineer)
**Scope:** Core architecture, Jinja2 templates, MCP tooling, and existing PHP implementation.

---

## 🛡️ Executive Summary

The AI-First PHP Monolith template provides a solid foundation for rapid, secure development using modern patterns like HTMX and PDO. It includes robust CSRF protection, secure database connectivity, and basic authentication with rate limiting. 

However, several critical gaps exist that must be addressed before the template is used for production-level applications, particularly regarding **Authorization (RBAC)**, **Session Security**, and **Standardized Ownership Checks**.

---

## 🚩 High-Fidelity Vulnerability Findings

### 1. Missing Authentication on Core Dashboard
- **Vulnerability:** Unauthenticated Access to Private Data
- **Vulnerability Type:** Security (Broken Access Control)
- **Severity:** High
- **Source Location:** `src/public/index.php`
- **Line Content:** No auth check at the top of the file.
- **Description:** The main dashboard (`index.php`) displays sensitive information (shortcuts, reminders, apps) but does not verify if a user is logged in. This exposes private data to any unauthenticated visitor.
- **Recommendation:** Add a session-based authentication check at the top of `index.php`, similar to the logic in `empty_page.php.j2`.

### 2. Lack of Role-Based Access Control (RBAC)
- **Vulnerability:** Missing Authorization Framework
- **Vulnerability Type:** Security (Authorization)
- **Severity:** High
- **Source Location:** `src/builder/templates/user_model.php.j2`, `src/builder/mcp_server.py`
- **Line Content:** `CREATE TABLE IF NOT EXISTS users (...)` (No role column)
- **Description:** There is no mechanism to distinguish between "User" and "Admin" roles. All authenticated users have the same level of access. This makes it impossible to build applications with restricted administrative areas.
- **Recommendation:** 
    1. Add a `role` column (e.g., `ENUM('user', 'admin') DEFAULT 'user'`) to the `users` table in `mcp_server.py`.
    2. Update the `User` model to handle roles.
    3. Update `empty_page.php.j2` to support an `admin_required` flag.

### 3. Missing Security Headers
- **Vulnerability:** Lack of Browser-Level Protections
- **Vulnerability Type:** Security (Configuration)
- **Severity:** Medium
- **Source Location:** `src/public/db.php` (or a global header file)
- **Line Content:** N/A (Missing)
- **Description:** The application does not set critical security headers like `Content-Security-Policy` (CSP), `X-Content-Type-Options`, `X-Frame-Options`, or `Strict-Transport-Security` (HSTS). This leaves the application vulnerable to XSS, Clickjacking, and MIME-sniffing attacks.
- **Recommendation:** Add a `header.php` or update `db.php` to emit these headers on every request.

### 4. Registration Endpoint Rate Limiting
- **Vulnerability:** Unprotected Registration (Bot Spam/Account Exhaustion)
- **Vulnerability Type:** Security (Rate Limiting)
- **Severity:** Medium
- **Source Location:** `src/builder/templates/register_page.php.j2`
- **Line Content:** No rate-limiting logic in the POST handler.
- **Description:** Unlike `login.php`, the `register.php` page is not rate-limited. An attacker could automate account creation to exhaust database resources or spam the system.
- **Recommendation:** Implement IP-based rate limiting for the registration endpoint using the existing `login_attempts` or a similar `registration_attempts` table.

### 5. Weak Session Management
- **Vulnerability:** Incomplete Session Security
- **Vulnerability Type:** Security (Session)
- **Severity:** Medium
- **Source Location:** `src/public/db.php`, `src/public/login.php`
- **Line Content:** Missing `session_set_cookie_params` configuration.
- **Description:** While `session_regenerate_id(true)` is used, explicit session timeouts and secure cookie flags (Secure, HttpOnly, SameSite) are not consistently enforced in code. Relying on `php.ini` defaults is risky for a portable template.
- **Recommendation:** Use `session_set_cookie_params` in `db.php` to enforce `httponly`, `secure` (if HTTPS), and `samesite=Lax`. Implement a server-side session timeout check.

---

## 🛠️ MCP-Tooling & Jinja Template Gaps

The following gaps in the "Factory" tools prevent the AI from generating production-ready secure code:

1. **`scaffold_auth` Gaps:**
    - Does not support **Email Verification**.
    - Does not support **Password Reset** (Forgot Password).
    - Hardcoded SQL in `mcp_server.py` should be moved to a `.sql` template for consistency.

2. **`create_model` Gaps:**
    - Does not automatically include **Ownership Checks** (`user_id`). For production, most models should include a `user_id` column and the generated `getAll()` or `find()` methods should filter by the logged-in user.

3. **`add_htmx_form` Gaps:**
    - Does not support **Server-Side Validation Feedback** in a standardized way (e.g., returning 422 with error messages that HTMX can swap into specific fields).

---

## ✅ Best Practices Already Followed
- **SQL Injection:** Excellent use of PDO and prepared statements.
- **CSRF:** Implemented correctly for both standard and HTMX requests.
- **Credential Safety:** Uses `password_hash` and `password_verify`.
- **Environment:** Correct use of `getenv` for secrets.

---

## 📈 Next Steps & Remediation Plan

1. **Immediate:** Protect `index.php` with an authentication check.
2. **Short-term:** Update `mcp_server.py` to include a `role` column in the `users` table and implement basic RBAC in `empty_page.php.j2`.
3. **Medium-term:** Create a `SecurityManager` class to handle standardized validation, rate limiting, and security headers.
