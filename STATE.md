# Project State: Modern PHP Monolith

**Current Date:** Sunday, January 11, 2026
**Status:** Ready for First Build

## 1. Directory Structure

```text
/home/chris/repos/ai-first-php-monolith/
├── docker-compose.yml       # Orchestrates App (Monolith), DB (MySQL), PMA
├── Dockerfile               # The "Factory" Image: PHP 8.2 + Apache + Python + UV + Tailwind
├── README.md                # Philosophy and Setup Guide
├── src/
│   ├── builder/             # 🔒 HIDDEN from Web. The "AI Workshop".
│   │   ├── mcp_server.py    # The MCP Server (Python/FastMCP)
│   │   └── templates/       # Jinja2 Blueprints
│   │       ├── feature_page.php.j2  # UI Template (HTMX + Tailwind)
│   │       ├── layout.php.j2        # Master Layout
│   │       └── pdo_class.php.j2     # Secure Data Access Object
│   ├── input.css            # Tailwind Source
│   ├── public/              # 🌍 EXPOSED to Web. Apache DocumentRoot.
│   │   ├── .htaccess        # Security rules (No indexes, routing)
│   │   ├── csrf.php         # Security helper (CSRF Tokens)
│   │   ├── db.php           # Database Connection (PDO)
│   │   ├── index.php        # Landing Page
│   │   ├── classes/         # Generated PHP Classes go here (future)
│   │   └── style.css        # Generated CSS (future)
│   └── scripts/             # Utility scripts (Perl/Python)
└── .gitignore
```

## 2. The "Factory" Workflow

This stack is designed for **Self-Replication**. You do not write PHP code manually; you ask the AI to invoke the `builder` tools.

### Comparison: Old Stack vs. New Stack

| Feature | Old Stack (FastAPI + Next.js) | New Stack (PHP Monolith) |
| :--- | :--- | :--- |
| **Data Models** | Pydantic Schemas (`models/user.py`) | PDO Classes (`public/classes/User.php`) |
| **API/Logic** | FastAPI Routes (`api/endpoints/users.py`) | PHP Page Logic (Embedded in `users.php`) |
| **Frontend** | React Components + Hooks | HTML + Jinja2 Injection + HTMX |
| **Migrations** | Alembic (Complex autogen) | `execute_sql` (Direct DDL) |
| **Build Step** | `npm run build` (Slow) | Zero. `build_css` (Fast) |

## 3. Capabilities (MCP Tools)

The embedded MCP server (`src/builder/mcp_server.py`) exposes a modular "Lego Block" toolset:

### A. `create_model(name, fields)`
*   **Purpose:** The Data Layer Generator.
*   **Action:** Creates a secure PDO Model class in `src/public/classes/`.
*   **Use Case:** When you need backend logic (e.g., for an API or background task) but want to build a custom UI.

### B. `create_page(filename, title, models=[])`
*   **Purpose:** The "Blank Canvas" Generator.
*   **Action:** Creates a new PHP file pre-wired with:
    *   Database Connection (`$pdo`)
    *   CSRF Protection
    *   Requested Models (instantiated as `$userModel`, etc.)
    *   Master Layout
*   **Use Case:** "Create a Dashboard" or "Create a Landing Page".

### C. `scaffold_crud(name, fields)`
*   **Purpose:** The "All-in-One" Generator.
*   **Action:** Calls `create_model` AND generates a CRUD UI shell.
*   **Use Case:** Rapidly building admin tables or simple lists.

### D. `execute_sql(query)`
*   **Purpose:** Database management.
*   **Purpose:** Database management.
*   **Input:** `CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255)...)`
*   **Action:** Runs the SQL directly against the MySQL container.
*   **Use Case:** Creating tables for new features or modifying schema.

### C. `build_css(minify=False)`
*   **Purpose:** Styling engine.
*   **Action:** Runs the standalone `tailwindcss` binary.
*   **Logic:** Scans all `.php` files in `src/public` for Tailwind classes and compiles them into `src/public/style.css`.
*   **Use Case:** Run this after generating a new feature to ensure its styles are applied.

### D. `run_perl_script(name)`
*   **Purpose:** Legacy or system-level tasks.
*   **Use Case:** Data processing or system maintenance scripts.

## 4. Security & Safety

*   **Isolation:** The `src/builder` directory is outside the Apache `DocumentRoot`. The web server physically cannot serve your templates or python scripts.
*   **Injection Protection:** The `pdo_class` template uses strictly bound parameters (`:value`), making SQL injection impossible for generated code.
*   **CSRF:** All generated forms include automated CSRF token generation and verification.
*   **Environment:** Production errors are suppressed; Local errors are shown.

## 5. Next Steps

1.  **Build & Start:** Run `docker compose up -d --build`.
2.  **Initialize:** Create your first table using `execute_sql`.
3.  **Generate:** Create a feature using `generate_feature`.
