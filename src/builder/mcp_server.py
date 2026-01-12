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
def create_page(filename: str, title: str, models: List[str] = []):
    """
    Creates a blank PHP page wired with DB, CSRF, and requested Models.
    Args:
        filename: The output filename (e.g., 'dashboard.php').
        title: The page <title>.
        models: List of PascalCase model names to include and instantiate (e.g. ['User', 'Invoice']).
    """
    # Ensure filename ends in .php
    if not filename.endswith('.php'):
        filename += '.php'

    ctx = {
        "page_name": filename,
        "page_title": title,
        "models": models
    }

    page_path = os.path.join(PUBLIC_DIR, filename)
    template = templates_env.get_template("empty_page.php.j2")
    
    with open(page_path, "w") as f:
        f.write(template.render(ctx))
        
    return f"Created Page: src/public/{filename} (Includes: {', '.join(models) if models else 'None'})"

@mcp.tool()
def scaffold_crud(name: str, fields: List[str]):
    """
    High-Level Macro: Generates BOTH a Model and a CRUD Page.
    Args:
        name: The singular snake_case name (e.g., 'todo_item').
        fields: List of fields (e.g. ['title:string', 'is_done:boolean']).
    """
    # 1. Create Model
    model_result = _create_model_internal(name, fields)
    
    # 2. Create CRUD Page
    pascal_name = to_pascal_case(name)
    plural_name = to_plural(name)
    parsed_fields = parse_fields(fields)
    
    ctx = {
        "name": name,
        "pascal_name": pascal_name,
        "plural_name": plural_name,
        "fields": parsed_fields
    }
    
    page_path = os.path.join(PUBLIC_DIR, f"{plural_name}.php")
    template = templates_env.get_template("feature_page.php.j2")
    
    with open(page_path, "w") as f:
        f.write(template.render(ctx))
        
    return f"Scaffold Complete.\n1. {model_result}\n2. Created CRUD Page: src/public/{plural_name}.php"

@mcp.tool()
def execute_sql(query: str):
    """
    Executes a raw SQL query against the MySQL database.
    """
    try:
        conn = mysql.connector.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME
        )
        cursor = conn.cursor()
        cursor.execute(query)
        
        result = None
        if cursor.with_rows:
            result = cursor.fetchall()
        
        conn.commit()
        cursor.close()
        conn.close()
        
        return f"Query executed successfully. Result: {result}"
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