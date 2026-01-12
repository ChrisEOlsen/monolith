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
*   **Option A (Standard CRUD):** Use `scaffold_crud(name='project', fields=['name:string', 'status:string'])`.
    *   *Why?* It generates the Model AND the UI shell in one go.
*   **Option B (Custom):** 
    1.  Use `create_model(name='project', ...)` to get the secure PDO class.
    2.  Use `create_page(filename='dashboard.php', models=['Project'])` to get the empty Controller/View shell.

### 3. 🎨 Paint the Interface
*   **Think:** Now that the logic works, how should it look?
*   **Action:** Edit the generated `.php` file (e.g., `src/public/projects.php`).
*   **Rule:** 
    *   Keep PHP logic at the top (Controller).
    *   Keep HTML at the bottom (View).
    *   Use **Tailwind CSS** classes directly in the HTML.
    *   Use **HTMX** for interactions (like `hx-post`, `hx-target`) instead of writing custom JS.

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

4.  **Security:**
    *   Always rely on the `csrf_token` verification that comes pre-installed in the templates.

## 🛠️ Tool Cheat Sheet

| Tool | When to use |
| :--- | :--- |
| `execute_sql` | Creating tables or modifying schema. |
| `scaffold_crud` | 80% of use cases. Creates Model + CRUD UI. |
| `create_model` | When you need data access but a custom UI. |
| `create_page` | When you need a blank page (Landing, Dashboard). |
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
    "php-monolith-app",
    "/opt/builder_venv/bin/python",
    "/var/www/html/builder/mcp_server.py"
  ]
}
```
