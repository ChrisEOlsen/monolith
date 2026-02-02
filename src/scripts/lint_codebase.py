import os
import subprocess
import re
import sys

BASE_DIR = "/var/www/html/public"

BANNED_PATTERNS = [
    (r'echo\s+\$_GET', "Possible XSS: Direct output of $_GET"),
    (r'echo\s+\$_POST', "Possible XSS: Direct output of $_POST"),
    (r'->query\(.*\$', "Possible SQL Injection: Raw query with variable interpolation"),
    (r'<script>\s*fetch\(', "Architecture Violation: Custom fetch() used. Use HTMX."),
    (r'axios\.', "Architecture Violation: Axios used. Use HTMX.")
]

def check_php_syntax(file_path):
    result = subprocess.run(["php", "-l", file_path], capture_output=True, text=True)
    return result.returncode == 0, result.stderr.strip() if result.returncode != 0 else ""

def check_patterns(file_path):
    issues = []
    try:
        with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
            content = f.read()
            for pattern, msg in BANNED_PATTERNS:
                if re.search(pattern, content):
                    issues.append(msg)
    except Exception as e:
        issues.append(f"Could not read file: {str(e)}")
    return issues

def main():
    has_errors = False
    print("Running Linter...")
    
    for root, _, files in os.walk(BASE_DIR):
        for file in files:
            if file.endswith(".php"):
                path = os.path.join(root, file)
                rel_path = os.path.relpath(path, BASE_DIR)
                
                # 1. Syntax Check
                valid, err = check_php_syntax(path)
                if not valid:
                    print(f"❌ [Syntax] {rel_path}: {err}")
                    has_errors = True
                    continue
                
                # 2. Pattern Check
                issues = check_patterns(path)
                if issues:
                    for issue in issues:
                        print(f"⚠️ [Pattern] {rel_path}: {issue}")
                        has_errors = True
    
    if not has_errors:
        print("✅ Codebase is clean.")
    else:
        sys.exit(1)

if __name__ == "__main__":
    main()
