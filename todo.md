# Future Tasks for AI PHP Monolith

- [ ] **Create `deploy_to_production` MCP Tool**
    - **Goal:** Automate the transition from "Local Dev" to "Production Ready".
    - **Actions:**
        - Remove development-specific code/configurations (e.g., auto-login in PMA, raw error display).
        - Update `docker-compose.yml`:
            - Remove/Restrict direct port mappings (e.g., 8080).
            - Enable Traefik labels for reverse proxy and SSL.
            - Consider using Jinja2 templates for dynamic Traefik configuration (e.g., domain names).
        - Ensure `APP_ENV` is set to `prod`.
        - Build production assets (CSS).
