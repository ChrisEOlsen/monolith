---
description: Deploy the app live via Cloudflare tunnel and generate an Obsidian knowledge graph
---

You are running the deployment workflow. Only run this after the developer has reviewed and approved the running app from `/build`.

---

## Step 1: Check Prerequisites

Read `.env` and verify these vars exist and are non-empty:

- `CLOUDFLARE_API_TOKEN`
- `CLOUDFLARE_TUNNEL_TOKEN`
- `CLOUDFLARE_DOMAIN`

If any are missing, **STOP** and tell the developer:

> "The following vars are missing from .env: [list them]. Add them and run /launch again."

---

## Step 2: Add Cloudflare Tunnel to docker-compose.yml

Check if `docker-compose.yml` already has a `cloudflared:` service. If not, append it.

Read `docker-compose.yml` and add the following service under `services:`, before the closing `volumes:` or `networks:` block:

```yaml
  cloudflared:
    image: cloudflare/cloudflared:latest
    command: tunnel --no-autoupdate run
    environment:
      - TUNNEL_TOKEN=${CLOUDFLARE_TUNNEL_TOKEN}
    networks:
      - default
    depends_on:
      - app
```

If the service already exists, skip this step.

---

## Step 3: Cloudflare Tunnel

Use the Cloudflare MCP server tools to:

1. Create or configure a tunnel using `CLOUDFLARE_TUNNEL_TOKEN`
2. Configure routing so that `CLOUDFLARE_DOMAIN` points to the tunnel → the local app

---

## Step 4: Rebuild Containers

Run:

```bash
docker compose up -d --build
```

Wait for the containers to come up cleanly before continuing.

---

## Step 5: Knowledge Graph

Use the `graphify` skill on `src/public/`.

This generates a navigable knowledge graph of the application codebase, suitable for viewing in Obsidian or a browser.

---

## Step 6: Report

Tell the developer:

> **Deployment complete.**
>
> Live at: `https://[CLOUDFLARE_DOMAIN]`
> Knowledge graph: `graphify-out/`
>
> Open `graphify-out/index.html` in a browser, or import the JSON into Obsidian.
