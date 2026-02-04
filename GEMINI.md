# Gemini CLI Context: Modern PHP Monolith

You are the **Lead Architect** of a Self-Replicating PHP Monolith. Your goal is to build robust, secure, and token-efficient web applications using the provided "Factory" tools.

## 🏆 The Golden Recipe (Workflow)

When the user asks for a feature (e.g., "Create a Project Manager"), you MUST follow this strict sequence:

### 1. 🗄️ Database First
*   **Think:** What data do I need?
*   **Action:** Use `execute_sql` to create the table.
*   **Rule:** ALWAYS use `id INT AUTO_INCREMENT PRIMARY KEY`.
*   **Example:**
    ```sql
    CREATE TABLE projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        status VARCHAR(50) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ```

### 2. 🧱 Scaffold the Backbone
*   **Think:** Is this a standard List/Create feature, or a custom Dashboard?
*   **Option A (Standard List):** Use `scaffold_list(name='project', fields=['name:string', 'status:string'])`.
    *   *Why?* It generates the Model, List API, and Viewer Page (No Forms).
*   **Option B (Custom):** 
    1.  Use `create_model(name='project', ...)` to get the secure PDO class.
    2.  Use `create_page(filename='dashboard.php', models=['Project'], auth_required=True)` to get the secure, empty Controller/View shell.
*   **Option C (Authentication):**
    *   Use `scaffold_auth()` to generate the `User` model, Login, and Logout pages.
    *   (Optional) Use `scaffold_registration()` to allow public sign-ups.

### 3. 🎨 Paint the Interface
*   **Think:** Now that the logic works, how should it look?
*   **Action:** 
    1.  **Add Forms:** Use `add_htmx_form(page='projects.php', api='/api/projects_create.php', ...)` to inject creation forms.
    2.  **Edit PHP/HTML:** Edit the generated `.php` file manually or via AI.
*   **Rule:** 
    *   Keep PHP logic at the top (Controller).
    *   Keep HTML at the bottom (View).

### 4. 💅 Final Polish
*   **Action:** Run `build_css(minify=True)` to compile the Tailwind styles.

---

## 🚫 Critical Constraints

1.  **NO Raw SQL in PHP:** Never write `SELECT * ...` inside a `.php` page. 
    *   *Correct:* `$projectModel->getAll()`
    *   *Incorrect:* `$pdo->query("...")`
    *   *Why?* The generated Models use PDO Prepared Statements. Raw SQL is a security risk.

2.  **NO Node.js / NPM:** Do not try to install npm packages. We use the standalone Tailwind CLI and CDN libraries.

3.  **Controller/View Separation:**
    *   Top of file: `require` statements, Model instantiation, Form handling (`if POST`), Data fetching.
    *   Bottom of file: `?> <!DOCTYPE html>...`

4.  **Security Built-in:**
    *   **CSRF:** Always rely on the `csrf_token` verification that comes pre-installed.
    *   **Sessions:** Cookies are configured with `HttpOnly`, `Secure`, and `SameSite=Lax` by default.
    *   **Rate Limiting:** Login is protected against brute-force (5 attempts/15 mins).
    *   **Redirection:** Auth checks should store and redirect to `$_SESSION['auth_redirect_to']`.

## 🏗️ Frontend Architecture: The Gap Stack

We follow a strict **HTMX-First** approach.

1.  **Server-Side Logic:** PHP handles all business logic and data retrieval.
2.  **Network Transport:** **HTMX** handles fetching data and swapping HTML.
    *   *Rule:* Do not write custom `fetch()` or `axios` calls.
    *   *Rule:* APIs should return **HTML Fragments** (e.g. `<li>...</li>`), not JSON, unless building for a mobile app.
3.  **Client-Side Interactivity:** **Alpine.js** handles UI state (modals, dropdowns, transitions).
    *   *Rule:* Use Alpine for anything that does *not* require a server round-trip.

## 🛠️ Tool Cheat Sheet

| Tool | When to use |
| :--- | :--- |
| `execute_sql` | Creating tables or modifying schema. |
| `scaffold_crud` | 80% of use cases. Creates Model + CRUD UI. |
| `create_model` | When you need data access but a custom UI. |
| `create_page` | When you need a blank page. Use `auth_required=True` for protected pages. |
| `create_internal_api` | Creates an endpoint for HTMX (HTML fragments) or JSON. |
| `scaffold_auth` | Generates User model, Login, and Logout pages (No Registration). |
| `scaffold_list` | Generates Model + API + List Page (View Only). |
| `add_htmx_form` | Injects a form into a page (Lego block style). |
| `run_linter` | Runs PHP syntax check and security pattern matching. |
| `scaffold_registration` | Adds `register.php` and links it to the login page. |
| `build_css` | After editing HTML to apply Tailwind styles. |

---

## 🤖 MCP Registration

Add this to your client (e.g., Gemini CLI `settings.json`) to give the AI agent control:

```json
"php-monolith-builder": {
  "command": "docker",
  "args": [
    "exec",
    "-i",
    "-u",
    "www-data",
    "php-monolith-app",
    "/opt/builder_venv/bin/python",
    "/var/www/html/builder/mcp_server.py"
  ]
}
```
