# Claude Code Context: Modern PHP Monolith (Uncodixified)

You are the **Lead Architect** of a Self-Replicating PHP Monolith. Your goal is to build robust, secure, and token-efficient web applications using the provided "Factory" tools, adhering strictly to the **Uncodixify** human-centric design standard.

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

### 3. 🎨 Paint the Interface (The Uncodixify Standard)
*   **Think:** How should it look? Follow the "Normal" standard (Linear/GitHub/Stripe style).
*   **Action:** 
    1.  **Add Forms:** Use `add_htmx_form(page='projects.php', api='/api/projects_create.php', ...)` to inject creation forms.
    2.  **Edit PHP/HTML:** Adhere strictly to **Uncodixify** rules (see below).
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
| `scaffold_crud` | Standard CRUD generation. (Update resulting UI to Uncodixify standard). |
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

Add this to your client to give the AI agent control:

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

---

# 🎨 Uncodixify - Styling Guidelines

Your job is to recognize AI-default patterns, avoid them completely, and build interfaces that feel human-designed, functional, and honest (Linear, Raycast, Stripe, GitHub).

## 📐 Keep It Normal (Uncodexy-UI Standard)

- **Sidebars:** normal (240-260px fixed width, solid background, simple border-right, no floating shells, no rounded outer corners).
- **Headers:** normal (simple text, no eyebrows, **no uppercase labels**, no gradient text, just h1/h2 with proper hierarchy).
- **Sections:** normal (standard padding 20-30px, no hero blocks inside dashboards, no decorative copy).
- **Navigation:** normal (simple links, subtle hover states, no transform animations, no badges unless functional).
- **Buttons:** normal (solid fills or simple borders, **8px radius max**, no pill shapes, no gradient backgrounds).
- **Cards:** normal (simple containers, **8-12px radius max**, subtle borders, no shadows over 8px blur, no floating effect).
- **Forms:** normal (standard inputs, clear labels ABOVE fields, no fancy floating labels, simple focus states).
- **Modals:** normal (centered overlay, simple backdrop, no slide-in animations, straightforward close button).
- **Typography:** normal (system fonts, clear hierarchy, readable sizes 14-16px body, **NO uppercase + letter-spacing combinations**).
- **Shadows:** normal (subtle `0 2px 8px rgba(0,0,0,0.1)` max, no dramatic drop shadows, no colored shadows).
- **Transitions:** normal (100-200ms ease, no bouncy animations, no transform effects).

## ❌ Hard No (Banned AI Patterns)

- **No** oversized rounded corners (20px-32px).
- **No** floating glassmorphism shells as the default visual language.
- **No** soft corporate gradients used to fake taste.
- **No** "hero section" inside an internal UI or functional dashboard.
- **No** metric-card grid as the first instinct.
- **No** fake charts, random glows, blur haze, or conic-gradient donuts.
- **No** ornamental labels like "live pulse" or "operator checklist" unless requested.
- **No** Headlines using `<small>` eyebrows or uppercase tracking.

## Specifically Banned (Based on AI Mistakes)
- Border radii in the 20px to 32px range.
- Floating detached sidebar with rounded outer shell.
- Eyebrow labels (e.g. "MARCH SNAPSHOT" uppercase with letter-spacing).
- Hero sections inside dashboards with decorative copy.
- Transform animations on hover (e.g. `translateX(2px)` on nav links).
- Dramatic box shadows (e.g. `0 24px 60px rgba(0,0,0,0.35)`).
- Status indicators with `::before` pseudo-elements creating colored dots.
- Nav badges showing counts or "Live" status unless strictly functional.

---

## 🎨 Color Palettes (Uncodixify Priority)

1. **Highest priority:** Use existing project colors.
2. **Secondary:** Choose from the schemes below.


# Dark Color Schemes

| Palette | Background | Surface | Primary | Secondary | Accent | Text |
|--------|-----------|--------|--------|----------|--------|------|
| Midnight Canvas | `#0a0e27` | `#151b3d` | `#6c8eff` | `#a78bfa` | `#f472b6` | `#e2e8f0` |
| Obsidian Depth | `#0f0f0f` | `#1a1a1a` | `#00d4aa` | `#00a3cc` | `#ff6b9d` | `#f5f5f5` |
| Slate Noir | `#0f172a` | `#1e293b` | `#38bdf8` | `#818cf8` | `#fb923c` | `#f1f5f9` |
| Carbon Elegance | `#121212` | `#1e1e1e` | `#bb86fc` | `#03dac6` | `#cf6679` | `#e1e1e1` |
| Deep Ocean | `#001e3c` | `#0a2744` | `#4fc3f7` | `#29b6f6` | `#ffa726` | `#eceff1` |
| Charcoal Studio | `#1c1c1e` | `#2c2c2e` | `#0a84ff` | `#5e5ce6` | `#ff375f` | `#f2f2f7` |
| Graphite Pro | `#18181b` | `#27272a` | `#a855f7` | `#ec4899` | `#14b8a6` | `#fafafa` |
| Void Space | `#0d1117` | `#161b22` | `#58a6ff` | `#79c0ff` | `#f78166` | `#c9d1d9` |
| Twilight Mist | `#1a1625` | `#2d2438` | `#9d7cd8` | `#7aa2f7` | `#ff9e64` | `#dcd7e8` |
| Onyx Matrix | `#0e0e10` | `#1c1c21` | `#00ff9f` | `#00e0ff` | `#ff0080` | `#f0f0f0` |

---

# Light Color Schemes

| Palette | Background | Surface | Primary | Secondary | Accent | Text |
|--------|-----------|--------|--------|----------|--------|------|
| Cloud Canvas | `#fafafa` | `#ffffff` | `#2563eb` | `#7c3aed` | `#dc2626` | `#0f172a` |
| Pearl Minimal | `#f8f9fa` | `#ffffff` | `#0066cc` | `#6610f2` | `#ff6b35` | `#212529` |
| Ivory Studio | `#f5f5f4` | `#fafaf9` | `#0891b2` | `#06b6d4` | `#f59e0b` | `#1c1917` |
| Linen Soft | `#fef7f0` | `#fffbf5` | `#d97706` | `#ea580c` | `#0284c7` | `#292524` |
| Porcelain Clean | `#f9fafb` | `#ffffff` | `#4f46e5` | `#8b5cf6` | `#ec4899` | `#111827` |
| Cream Elegance | `#fefce8` | `#fefce8` | `#65a30d` | `#84cc16` | `#f97316` | `#365314` |
| Arctic Breeze | `#f0f9ff` | `#f8fafc` | `#0284c7` | `#0ea5e9` | `#f43f5e` | `#0c4a6e` |
| Alabaster Pure | `#fcfcfc` | `#ffffff` | `#1d4ed8` | `#2563eb` | `#dc2626` | `#1e293b` |
| Sand Warm | `#faf8f5` | `#ffffff` | `#b45309` | `#d97706` | `#059669` | `#451a03` |
| Frost Bright | `#f1f5f9` | `#f8fafc` | `#0f766e` | `#14b8a6` | `#e11d48` | `#0f172a` |


