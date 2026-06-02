# Knowledge Base Setup

This folder contains a local, Graphify-style knowledge base for the Laravel POS project.

It was created because the official `graphify` command was not installed on this machine, and `uv` was also not available on PATH. Instead of waiting on a global install, I added a repo-local generator that can be run with the Python already available here.

## What Was Created

- `tools/build_knowledge_base.py`
  - Scans the Laravel project.
  - Extracts routes, controllers, controller methods, model references, Eloquent relationships, migrations, table columns, Blade views, route links, tests, and Markdown sections.
  - Writes the generated knowledge base files into `graphify-out/`.

- `graphify-out/graph.html`
  - Interactive browser graph.
  - Open this directly in a browser.
  - No server is required.

- `graphify-out/graph.json`
  - Machine-readable graph data.
  - Useful for custom scripts, future AI-agent queries, or importing into another graph tool.

- `graphify-out/GRAPH_REPORT.md`
  - Human-readable summary of the graph.
  - Includes snapshot counts, key domains, most connected concepts, relationship types, and suggested questions.

## Steps I Followed

From the project root:

```powershell
cd C:\Users\Raihan\Herd\LaravelPos
```

I checked whether official Graphify was already available:

```powershell
Get-Command graphify -ErrorAction SilentlyContinue
```

No `graphify` command was found.

I checked Python:

```powershell
python --version
```

Python was available:

```text
Python 3.13.1
```

I checked `uv`:

```powershell
uv --version
```

`uv` was not available on PATH.

Then I created the local generator:

```text
tools/build_knowledge_base.py
```

Then I ran:

```powershell
python tools\build_knowledge_base.py
```

That generated:

```text
graphify-out\graph.html
graphify-out\graph.json
graphify-out\GRAPH_REPORT.md
```

I verified the generator compiles:

```powershell
python -m py_compile tools\build_knowledge_base.py
```

I verified the JSON is valid:

```powershell
python -m json.tool graphify-out\graph.json
```

## How To Regenerate The Knowledge Base

Run this any time the codebase changes:

```powershell
cd C:\Users\Raihan\Herd\LaravelPos
python tools\build_knowledge_base.py
```

Then open:

```text
graphify-out\graph.html
```

## What The Local Generator Scans

The local generator scans:

- `app`
- `routes`
- `database/migrations`
- `database/seeders`
- `resources/views`
- `tests`
- `README.md`
- `MIGRATION_HANDOFF.md`
- `INSTALLER_BROWSER_MODE.md`
- `nativePhp.md`

It intentionally skips:

- `vendor`
- `node_modules`
- `storage`
- `database/migrations/legacy`

The legacy migrations are skipped so the graph reflects the active Laravel app rather than old CI3/reference migrations.

## Current Output

The latest generated graph contains:

- Nodes: 2,679
- Edges: 3,388
- Routes: 159
- Controllers: 31
- Models: 65
- Tables/table refs: 125
- Views: 49

## Things Still Missing

The local generator is useful, but it is not the full official Graphify package.

Missing compared with official Graphify:

- No official `graphify` CLI installed yet.
- No official Codex Graphify skill installed yet.
- No Graphify query command such as `graphify query "..."`
- No official `graphify export callflow-html`
- No AI-assisted extraction for PDFs, Office files, images, or videos.
- No optional SQL/Neo4j/MCP/OpenAI/Gemini integrations.
- No automatic assistant instruction telling Codex to query the graph first.

Limitations of this local version:

- It uses static regex-based extraction, not full PHP AST parsing.
- It detects many relationships, but not every runtime dependency.
- It does not execute Laravel code.
- It does not inspect the live database.
- It does not infer business rules beyond what appears in source and Markdown.

## Recommended Next Steps

If you want the official Graphify setup, install `uv` first.

On Windows:

```powershell
winget install astral-sh.uv
```

If that asks for admin permission, run the same command in an Administrator PowerShell.

Then install the official package:

```powershell
uv tool install graphifyy
```

Important: the official PyPI package is named `graphifyy` with a double `y`, but the command it installs is `graphify`.

After that, from this project root:

```powershell
cd C:\Users\Raihan\Herd\LaravelPos
graphify install --platform codex --project
graphify .
```

PowerShell note:

```powershell
graphify .
```

Use `graphify .`, not `/graphify .`.

Optional official architecture export:

```powershell
graphify export callflow-html
```

## Optional Codex Config

The official Graphify README says Codex users should enable multi-agent support in:

```text
~\.codex\config.toml
```

Add or confirm:

```toml
[features]
multi_agent = true
```

This may require editing a user-profile file outside this repo. If permission is needed, do it manually or give approval before running commands that write outside the project.

## Practical Workflow

For now, use the local version like this:

```powershell
python tools\build_knowledge_base.py
```

Then inspect:

```text
graphify-out\GRAPH_REPORT.md
graphify-out\graph.html
```

When official Graphify is installed, use:

```powershell
graphify .
graphify query "Where does LAN transfer sync flow through the app?"
graphify query "Which migrations still alter active tables?"
graphify query "Which controllers touch VAT reporting?"
```

