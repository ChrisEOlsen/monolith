# Future Upgrades

## `/upgrade` Command

Use the graphify knowledge graph as a memory layer for incremental project upgrades.

**Problem it solves:** After `/build` creates the app, a future upgrade agent has no way to know what was already built without re-reading every file. The graph provides cheap, queryable context.

**Proposed workflow:**

```
/upgrade
  1. /graphify --update        incremental re-extraction, only changed files
  2. Query graph               what features/pages already exist?
  3. Read SEED.md              what was originally planned?
  4. Diff                      what's in SEED.md but not yet in the graph?
  5. Read UPGRADE.md           new features the developer wants added
  6. Check god_nodes           highest-connected nodes = highest-risk to modify
  7. superpowers:brainstorming with graph context + UPGRADE.md as input
  8. superpowers:writing-plans
  9. superpowers:subagent-driven-development
  10. /security:analyze        re-scan after new code lands
```

**Key insight:** `god_nodes` from graphify identify which parts of the codebase are most connected — these are the riskiest to modify and should be flagged to the developer before planning changes.

**Requires:** An `UPGRADE.md` template (similar to `SEED.md`) for the developer to describe new features before running `/upgrade`.

**Also useful:** Run `/graphify --mcp` during the upgrade session to give subagents graph query tools (`query_graph`, `get_neighbors`, `shortest_path`) instead of reading files — significant token savings on large apps.
