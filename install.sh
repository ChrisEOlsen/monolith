#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CLAUDE_DIR="$HOME/.claude"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

ok()   { echo -e "  ${GREEN}✓${NC} $1"; }
warn() { echo -e "  ${YELLOW}!${NC} $1"; }
fail() { echo -e "  ${RED}✗${NC} $1"; exit 1; }
step() { echo -e "\n${BOLD}▶ $1${NC}"; }

echo ""
echo -e "${BOLD}PHP Monolith Factory — Setup${NC}"
echo "======================================"

# ── Prerequisites ──────────────────────────────────────────────────────────────
step "Checking prerequisites"

command -v python3 >/dev/null 2>&1 || fail "python3 not found — install Python 3.10+"
command -v docker  >/dev/null 2>&1 || fail "docker not found — install Docker"
command -v git     >/dev/null 2>&1 || fail "git not found"
command -v curl    >/dev/null 2>&1 || fail "curl not found"

ok "python3, docker, git, curl all present"

command -v stripe >/dev/null 2>&1 \
    && ok "stripe CLI present" \
    || warn "stripe CLI not found — install it for local webhook testing: https://stripe.com/docs/stripe-cli"

# ── Read APP_NAME from .env (fall back to env.example, then default) ───────────
ENV_FILE="$SCRIPT_DIR/.env"
EXAMPLE_FILE="$SCRIPT_DIR/env.example"

if [ -f "$ENV_FILE" ]; then
    APP_NAME=$(grep -E '^APP_NAME=' "$ENV_FILE" | head -1 | cut -d= -f2 | tr -d '"' | tr -d "'")
elif [ -f "$EXAMPLE_FILE" ]; then
    APP_NAME=$(grep -E '^APP_NAME=' "$EXAMPLE_FILE" | head -1 | cut -d= -f2 | tr -d '"' | tr -d "'")
fi
APP_NAME="${APP_NAME:-php-monolith}"
CONTAINER_NAME="${APP_NAME}-app"

ok "App name: $APP_NAME  |  Container: $CONTAINER_NAME"

# ── Ensure ~/.claude skill dirs exist ─────────────────────────────────────────
mkdir -p "$CLAUDE_DIR/skills/graphify"
mkdir -p "$CLAUDE_DIR/skills/uncodixify"

# ── 1. Graphify ───────────────────────────────────────────────────────────────
step "Installing graphify skill"

PIP_CMD=""
if command -v pip3 >/dev/null 2>&1; then
    PIP_CMD="pip3"
elif command -v pip >/dev/null 2>&1; then
    PIP_CMD="pip"
fi

GRAPHIFY_INSTALLED=false
if [ -n "$PIP_CMD" ]; then
    if $PIP_CMD install graphifyy -q 2>/dev/null; then
        if command -v graphify >/dev/null 2>&1; then
            graphify install 2>/dev/null && GRAPHIFY_INSTALLED=true
        fi
    fi
fi

if [ "$GRAPHIFY_INSTALLED" = false ]; then
    warn "pip install failed or graphify CLI not found — using manual install"
    curl -fsSL "https://raw.githubusercontent.com/safishamsi/graphify/v1/skills/graphify/skill.md" \
        > "$CLAUDE_DIR/skills/graphify/SKILL.md"
    ok "graphify SKILL.md installed (manual)"
else
    ok "graphify installed via pip + graphify install"
fi

# ── 2. Uncodixify ─────────────────────────────────────────────────────────────
step "Installing uncodixify skill"

curl -fsSL "https://raw.githubusercontent.com/cyxzdev/Uncodixfy/main/SKILL.md" \
    > "$CLAUDE_DIR/skills/uncodixify/SKILL.md"
ok "uncodixify SKILL.md installed"

# ── 3. Superpowers + Stripe → ~/.claude/settings.json (global, not project-specific) ──
step "Configuring ~/.claude/settings.json"

python3 - <<'PYEOF'
import json, os, sys

settings_path = os.path.expanduser("~/.claude/settings.json")

try:
    with open(settings_path) as f:
        settings = json.load(f)
except (FileNotFoundError, json.JSONDecodeError):
    settings = {}

# ── Superpowers plugin ──
settings.setdefault("enabledPlugins", {})
if "superpowers@claude-plugins-official" not in settings["enabledPlugins"]:
    settings["enabledPlugins"]["superpowers@claude-plugins-official"] = True
    print("  + superpowers@claude-plugins-official added to enabledPlugins")
else:
    print("  - superpowers already registered, skipping")

# ── Stripe MCP (global — not project-specific) ──
settings.setdefault("mcpServers", {})
if "stripe" not in settings["mcpServers"]:
    settings["mcpServers"]["stripe"] = {
        "type": "http",
        "url": "https://mcp.stripe.com/v1/sse"
    }
    print("  + stripe MCP → mcp.stripe.com")
else:
    print("  - stripe MCP already registered, skipping")

with open(settings_path, "w") as f:
    json.dump(settings, f, indent=2)
    f.write("\n")
PYEOF

ok "~/.claude/settings.json updated"

# ── 4. php-monolith-builder → .mcp.json (project-scoped, container name is dynamic) ──
step "Generating .mcp.json"

python3 - "$CONTAINER_NAME" "$SCRIPT_DIR" <<'PYEOF'
import json, sys, os

container   = sys.argv[1]
project_dir = sys.argv[2]
mcp_path    = os.path.join(project_dir, ".mcp.json")

config = {
    "mcpServers": {
        "php-monolith-builder": {
            "command": "docker",
            "args": [
                "exec", "-i", "-u", "www-data",
                container,
                "/opt/builder_venv/bin/python",
                "/var/www/html/builder/mcp_server.py"
            ]
        }
    }
}

with open(mcp_path, "w") as f:
    json.dump(config, f, indent=2)
    f.write("\n")

print(f"  + .mcp.json → php-monolith-builder via {container}")
PYEOF

ok ".mcp.json generated (project-scoped)"

# ── 5. .env setup ─────────────────────────────────────────────────────────────
step "Checking .env"

if [ ! -f "$ENV_FILE" ]; then
    cp "$EXAMPLE_FILE" "$ENV_FILE"
    warn ".env not found — copied from env.example"
    warn "Edit .env with your values before using /build"
else
    ok ".env already exists"
fi

# ── 6. Docker ─────────────────────────────────────────────────────────────────
step "Starting Docker containers"

cd "$SCRIPT_DIR"
docker compose up -d --build

ok "Containers up"

# ── Done ──────────────────────────────────────────────────────────────────────
echo ""
echo "======================================"
echo -e "${GREEN}${BOLD}Setup complete!${NC}"
echo ""
echo "  1. Fill in SEED.md with your app idea"
echo "  2. Add API keys to .env  (Stripe / OpenRouter / Cloudflare)"
echo "  3. Open Claude Code:  claude"
echo "  4. Verify MCP tools:  /mcp"
echo "  5. Start building:    /build"
echo ""
echo -e "${YELLOW}Note:${NC} Superpowers plugin downloads on first Claude Code launch."
echo ""
