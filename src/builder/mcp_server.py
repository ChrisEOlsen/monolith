import os
import subprocess
import mysql.connector
from fastmcp import FastMCP
from jinja2 import Environment, FileSystemLoader
from typing import List, Optional

# Initialize FastMCP
mcp = FastMCP("PHP Monolith Builder")

# --- Configuration ---
BASE_DIR = "/var/www/html"
BUILDER_DIR = os.path.join(BASE_DIR, "builder")
TEMPLATES_DIR = os.path.join(BUILDER_DIR, "templates")
PUBLIC_DIR = os.path.join(BASE_DIR, "public")
SCRIPTS_DIR = os.path.join(BASE_DIR, "scripts")

# Jinja2 Setup
templates_env = Environment(loader=FileSystemLoader(TEMPLATES_DIR))

# Database Config
DB_HOST = os.getenv("DB_HOST", "db")
DB_NAME = os.getenv("DB_NAME", "myapp")
DB_USER = os.getenv("DB_USER", "user")
DB_PASS = os.getenv("DB_PASS", "password")

# --- Helper Functions ---
def to_pascal_case(snake_case: str) -> str: 
    return "".join(word.capitalize() for word in snake_case.split('_'))

def to_plural(snake_case: str) -> str:
    if snake_case.endswith('y'): return snake_case[:-1] + 'ies'
    if snake_case.endswith('s'): return snake_case + 'es'
    return snake_case + 's'

def parse_fields(fields: List[str]):
    parsed = []
    for f in fields:
        parts = f.split(':')
        if len(parts) >= 2:
            parsed.append({"name": parts[0], "type": parts[1]})
        else:
            parsed.append({"name": parts[0], "type": "string"})
    return parsed

def _create_model_internal(name: str, fields: List[str]) -> str:
    """Internal helper to create a model file."""
    pascal_name = to_pascal_case(name)
    plural_name = to_plural(name)
    parsed_fields = parse_fields(fields)

    ctx = {
        "name": name,
        "pascal_name": pascal_name,
        "plural_name": plural_name,
        "fields": parsed_fields
    }

    classes_dir = os.path.join(PUBLIC_DIR, "classes")
    os.makedirs(classes_dir, exist_ok=True)
    
    class_path = os.path.join(classes_dir, f"{pascal_name}.php")
    template = templates_env.get_template("pdo_class.php.j2")
    
    with open(class_path, "w") as f:
        f.write(template.render(ctx))
        
    return f"Created Model: src/public/classes/{pascal_name}.php"

# --- MCP Tools ---

@mcp.tool()
def create_model(name: str, fields: List[str]):
    """
    Creates a standalone PHP PDO Model Class.
    Args:
        name: The singular snake_case name (e.g., 'invoice_item').
        fields: List of fields (e.g. ['amount:integer', 'description:string']).
    """
    return _create_model_internal(name, fields)

@mcp.tool()
def create_page(filename: str, title: str, models: List[str] = [], auth_required: bool = False):
    """
    Creates a blank PHP page wired with DB, CSRF, and requested Models.
    Args:
        filename: The output filename (e.g., 'dashboard.php').
        title: The page <title>.
        models: List of PascalCase model names to include and instantiate (e.g. ['User', 'Invoice']).
        auth_required: If True, adds a secure session check at the top of the file.
    """
    # Ensure filename ends in .php
    if not filename.endswith('.php'):
        filename += '.php'

    ctx = {
        "page_name": filename,
        "page_title": title,
        "models": models,
        "auth_required": auth_required
    }

    page_path = os.path.join(PUBLIC_DIR, filename)
    template = templates_env.get_template("empty_page.php.j2")
    
    with open(page_path, "w") as f:
        f.write(template.render(ctx))
        
    return f"Created Page: src/public/{filename} (Includes: {', '.join(models) if models else 'None'}, Auth: {auth_required})"

@mcp.tool()
def create_internal_api(name: str, method: str = "GET", models: List[str] = [], auth_required: bool = False):
    """
    Creates an internal API endpoint in src/public/api/ for HTMX/JSON.
    Args:
        name: The endpoint name (e.g. 'project_stats' -> src/public/api/project_stats.php).
        method: HTTP method (GET, POST, etc.).
        models: List of PascalCase models to include.
        auth_required: If True, adds session/auth check.
    """
    # Ensure filename ends in .php
    if not name.endswith('.php'):
        filename = name + '.php'
    else:
        filename = name
        name = name[:-4]

    ctx = {
        "name": name,
        "method": method.upper(),
        "models": models,
        "auth_required": auth_required
    }

    api_dir = os.path.join(PUBLIC_DIR, "api")
    os.makedirs(api_dir, exist_ok=True)
    
    file_path = os.path.join(api_dir, filename)
    template = templates_env.get_template("api_endpoint.php.j2")
    
    with open(file_path, "w") as f:
        f.write(template.render(ctx))
        
    return f"Created Internal API: src/public/api/{filename} (Method: {method}, Auth: {auth_required})"

@mcp.tool()
def scaffold_list(name: str, fields: List[str]):
    """
    Generates a 'List View' feature: Model + HTMX List API + Read-Only UI Page.
    Args:
        name: Singular feature name (e.g. 'task').
        fields: List of fields (e.g. ['title:string', 'is_done:boolean']).
    """
    # 1. Create Model
    model_res = _create_model_internal(name, fields)
    
    # 2. Create Internal API (HTMX List)
    pascal_name = to_pascal_case(name)
    plural_name = to_plural(name)
    
    api_filename = f"{plural_name}_list.php"
    
    # Generate List API Content
    api_content = f"""<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../csrf.php';

# Auth Check
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {{ http_response_code(401); exit; }}

require_once __DIR__ . '/../classes/{pascal_name}.php';
${name}Model = new {pascal_name}($pdo);

try {{
    # Fetch Data
    $items = ${name}Model->getAll(); 
    
    echo '<ul id="{name}-ul" class="space-y-2">';
    if (empty($items)) {{
        echo '<li class="text-gray-500 italic">No {plural_name} found.</li>';
    }} else {{
        foreach ($items as $item) {{
            echo '<li class="p-3 bg-white shadow rounded flex justify-between border border-gray-100">';
            # Display the first non-id field
            echo '<span class="font-medium">' . htmlspecialchars(array_values($item)[1] ?? 'Item') . '</span>';
            echo '</li>';
        }}
    }}
    echo '</ul>';
}} catch (Exception $e) {{
    http_response_code(500);
    echo 'Error loading data.';
}}
"""
    os.makedirs(os.path.join(PUBLIC_DIR, "api"), exist_ok=True)
    with open(os.path.join(PUBLIC_DIR, "api", api_filename), "w") as f:
        f.write(api_content)

    # 3. Create UI Page
    page_filename = f"{plural_name}.php"
    
    parsed_fields = parse_fields(fields)
    ctx = {
        "name": name,
        "pascal_name": pascal_name,
        "plural_name": plural_name,
        "fields": parsed_fields
    }
    
    template = templates_env.get_template("list_page.php.j2")
    with open(os.path.join(PUBLIC_DIR, page_filename), "w") as f:
        f.write(template.render(ctx))

    return f"List Scaffold Complete for '{name}'.\n1. Model: src/public/classes/{pascal_name}.php\n2. API: src/public/api/{api_filename}\n3. UI: src/public/{page_filename}"

@mcp.tool()
def add_htmx_form(page_filename: str, api_endpoint: str, fields: List[str], title: str = "Form", submit_label: str = "Save"):
    """
    Injects a neutral, CSRF-protected HTMX form into an existing page.
    Args:
        page_filename: The target PHP file (e.g., 'profile.php').
        api_endpoint: The URL where the form posts (e.g., '/api/update_profile.php').
        fields: List of fields (e.g. ['email:email', 'bio:textarea', 'is_active:boolean']).
        title: Optional title for the form card.
        submit_label: Label for the submit button.
    """
    # Parse fields
    parsed_fields = parse_fields(fields)
    
    ctx = {
        "api_endpoint": api_endpoint,
        "fields": parsed_fields,
        "title": title,
        "submit_label": submit_label
    }
    
    template = templates_env.get_template("htmx_form.php.j2")
    form_html = template.render(ctx)
    
    page_path = os.path.join(PUBLIC_DIR, page_filename)
    if not os.path.exists(page_path):
        return f"Error: Page '{page_filename}' not found."
        
    try:
        with open(page_path, "r") as f:
            content = f.read()
            
        if "</main>" in content:
            new_content = content.replace("</main>", f"{form_html}\n    </main>")
            with open(page_path, "w") as f:
                f.write(new_content)
            return f"Successfully added form '{title}' to {page_filename}."
        else:
            return f"Error: Could not find </main> tag in {page_filename} to inject form."
            
    except Exception as e:
        return f"Error modifying file: {str(e)}"

@mcp.tool()
def scaffold_auth():
    """
    Scaffolds a complete authentication system:
    1. Creates 'users' table in MySQL.
    2. Generates User model (src/public/classes/User.php).
    3. Generates Login, Register, and Logout pages.
    """
    # 1. Create Users Table
    create_table_sql = """
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        attempts INT DEFAULT 1,
        last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        locked_until TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY unique_ip (ip_address)
    );
    """
    
    try:
        conn = mysql.connector.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME
        )
        cursor = conn.cursor()
        # Split and execute statements individually
        statements = create_table_sql.split(';')
        for stmt in statements:
            if stmt.strip():
                cursor.execute(stmt)
        conn.commit()
        cursor.close()
        conn.close()
    except Exception as e:
        return f"Error creating 'users' table: {str(e)}"

    # 2. Generate User Model
    classes_dir = os.path.join(PUBLIC_DIR, "classes")
    os.makedirs(classes_dir, exist_ok=True)
    
    # We use the specialized user_model template
    try:
        model_template = templates_env.get_template("user_model.php.j2")
        with open(os.path.join(classes_dir, "User.php"), "w") as f:
            f.write(model_template.render())
    except Exception as e:
        return f"Error creating User model: {str(e)}"

    # 3. Generate Auth Pages (Login & Logout only)
    pages = {
        "login.php": "login_page.php.j2",
        "logout.php": "logout.php.j2"
    }

    created_files = ["src/public/classes/User.php"]

    try:
        for filename, template_name in pages.items():
            template = templates_env.get_template(template_name)
            with open(os.path.join(PUBLIC_DIR, filename), "w") as f:
                f.write(template.render())
            created_files.append(f"src/public/{filename}")
    except Exception as e:
        return f"Error generating auth pages: {str(e)}"
        
    return f"Auth Scaffold Complete.\nFiles Created:\n" + "\n".join(created_files)

@mcp.tool()
def scaffold_registration():
    """
    Adds user registration capability to the authentication system.
    1. Generates register.php.
    2. Adds a 'Register' link to login.php.
    """
    # 1. Generate Register Page
    try:
        template = templates_env.get_template("register_page.php.j2")
        with open(os.path.join(PUBLIC_DIR, "register.php"), "w") as f:
            f.write(template.render())
    except Exception as e:
        return f"Error generating register.php: {str(e)}"

    # 2. Update login.php with link
    login_path = os.path.join(PUBLIC_DIR, "login.php")
    if os.path.exists(login_path):
        try:
            with open(login_path, "r") as f:
                content = f.read()
            
            # Simple check to avoid duplicates
            if "register.php" not in content:
                # Look for the Sign In button closing tag and append the link
                insertion_point = "</button>"
                link_html = """
                <a class="inline-block align-baseline font-bold text-sm text-blue-600 hover:text-blue-800" href="/register.php">
                    Register
                </a>"""
                
                if insertion_point in content:
                    # We look for the last occurrence in the form
                    parts = content.rsplit(insertion_point, 1)
                    if len(parts) == 2:
                        new_content = parts[0] + insertion_point + link_html + parts[1]
                        with open(login_path, "w") as f:
                            f.write(new_content)
        except Exception as e:
             return f"Created register.php but failed to update login.php: {str(e)}"

    return "Registration Scaffold Complete.\n1. Created src/public/register.php\n2. Updated src/public/login.php with link."

@mcp.tool()
def execute_sql(query: str):
    """
    Executes one or more semicolon-separated SQL queries against the MySQL database.
    Returns a list of results for each query.
    """
    try:
        conn = mysql.connector.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME
        )
        cursor = conn.cursor()
        
        # Split by semicolon and filter out empty strings
        statements = [s.strip() for s in query.split(';') if s.strip()]
        results = []
        
        for stmt in statements:
            cursor.execute(stmt)
            if cursor.with_rows:
                results.append(cursor.fetchall())
            else:
                results.append(f"Affected rows: {cursor.rowcount}")
        
        conn.commit()
        cursor.close()
        conn.close()
        
        if len(results) == 1:
            return f"Query executed successfully. Result: {results[0]}"
        return f"Multiple queries executed successfully. Results: {results}"
    except Exception as e:
        return f"SQL Error: {str(e)}"

@mcp.tool()
def build_css(minify: bool = False):
    """
    Builds the production CSS file using Tailwind CLI.
    """
    input_path = os.path.join(BASE_DIR, "input.css")
    output_path = os.path.join(PUBLIC_DIR, "style.css")
    
    cmd = ["tailwindcss", "-i", input_path, "-o", output_path]
    if minify:
        cmd.append("--minify")
        
    try:
        # Scan php files in public dir for classes
        cmd.extend(["--content", os.path.join(PUBLIC_DIR, "**/*.php")])
        
        result = subprocess.run(cmd, capture_output=True, text=True)
        if result.returncode != 0:
             return f"Error building CSS: {result.stderr}"
        return f"CSS built successfully to {output_path}."
    except Exception as e:
        return f"Build Error: {str(e)}"

@mcp.tool()
def run_linter():
    """
    Runs the static analysis linter (Syntax check + Pattern Guardrails).
    Returns a report of any issues found.
    """
    script_path = "/var/www/html/scripts/lint_codebase.py"
    try:
        # We run it using the venv python
        cmd = ["/opt/builder_venv/bin/python", script_path]
        result = subprocess.run(cmd, capture_output=True, text=True)
        
        output = result.stdout
        if result.stderr:
            output += "\nErrors:\n" + result.stderr
            
        return output
    except Exception as e:
        return f"Linter Execution Failed: {str(e)}"

@mcp.tool()
def run_perl_script(script_name: str, args: List[str] = []):
    """
    Executes a Perl script located in the scripts directory.
    """
    script_path = os.path.join(SCRIPTS_DIR, script_name)
    if not os.path.exists(script_path):
        return f"Error: Script '{script_name}' not found in {SCRIPTS_DIR}"

    try:
        cmd = ["perl", script_path] + args
        result = subprocess.run(cmd, capture_output=True, text=True)
        return f"Script Output:\n{result.stdout}\nErrors:\n{result.stderr}"
    except Exception as e:
        return f"Execution Error: {str(e)}"


if __name__ == "__main__":
    mcp.run()
