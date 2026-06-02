from __future__ import annotations

import html
import json
import re
from collections import Counter
from dataclasses import dataclass, field
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "graphify-out"

SCAN_DIRS = [
    "app",
    "routes",
    "database/migrations",
    "database/seeders",
    "resources/views",
    "tests",
]
DOC_FILES = ["README.md", "MIGRATION_HANDOFF.md", "INSTALLER_BROWSER_MODE.md", "nativePhp.md"]
SKIP_PARTS = {"vendor", "node_modules", "storage", "legacy"}


@dataclass
class Graph:
    nodes: dict[str, dict] = field(default_factory=dict)
    edges: list[dict] = field(default_factory=list)
    edge_keys: set[tuple[str, str, str]] = field(default_factory=set)

    def node(self, node_id: str, label: str, node_type: str, **attrs) -> str:
        if node_id not in self.nodes:
            data = {"id": node_id, "label": label, "type": node_type}
            data.update({k: v for k, v in attrs.items() if v not in (None, "", [])})
            self.nodes[node_id] = data
        else:
            self.nodes[node_id].update({k: v for k, v in attrs.items() if v not in (None, "", [])})
        return node_id

    def edge(self, source: str, target: str, edge_type: str, **attrs) -> None:
        key = (source, target, edge_type)
        if key in self.edge_keys:
            return
        self.edge_keys.add(key)
        data = {
            "source": source,
            "target": target,
            "type": edge_type,
            "confidence": attrs.pop("confidence", "EXTRACTED"),
        }
        data.update({k: v for k, v in attrs.items() if v not in (None, "", [])})
        self.edges.append(data)


def rel(path: Path) -> str:
    return path.relative_to(ROOT).as_posix()


def read(path: Path) -> str:
    return path.read_text(encoding="utf-8", errors="ignore")


def iter_files() -> list[Path]:
    files: list[Path] = []
    for directory in SCAN_DIRS:
        base = ROOT / directory
        if not base.exists():
            continue
        for path in base.rglob("*"):
            if path.is_file() and not any(part in SKIP_PARTS for part in path.parts):
                files.append(path)
    for name in DOC_FILES:
        path = ROOT / name
        if path.exists():
            files.append(path)
    return sorted(set(files))


def first_line_number(text: str, needle: str) -> int | None:
    before = text.find(needle)
    if before < 0:
        return None
    return text[:before].count("\n") + 1


def classify_file(path: Path) -> str:
    rp = rel(path)
    if rp.startswith("app/Http/Controllers"):
        return "controller_file"
    if rp.startswith("app/Models"):
        return "model_file"
    if rp.startswith("app/Services"):
        return "service_file"
    if rp.startswith("routes/"):
        return "route_file"
    if rp.startswith("database/migrations"):
        return "migration_file"
    if rp.startswith("database/seeders"):
        return "seeder_file"
    if rp.startswith("resources/views"):
        return "view_file"
    if rp.startswith("tests/"):
        return "test_file"
    if path.suffix == ".md":
        return "doc_file"
    return "file"


def add_file_node(graph: Graph, path: Path) -> str:
    rp = rel(path)
    return graph.node(f"file:{rp}", rp, classify_file(path), file=rp)


def parse_php_file(graph: Graph, path: Path, text: str, file_id: str) -> None:
    rp = rel(path)

    namespace_match = re.search(r"namespace\s+([^;]+);", text)
    namespace = namespace_match.group(1).strip() if namespace_match else None

    classes: list[tuple[str, str, int | None]] = []
    for match in re.finditer(r"\bclass\s+(?!extends\b)([A-Za-z_][A-Za-z0-9_]*)\b(?:\s+extends\s+([A-Za-z_\\][A-Za-z0-9_\\]*))?", text):
        class_name = match.group(1)
        full_name = f"{namespace}\\{class_name}" if namespace else class_name
        class_type = "class"
        if rp.startswith("app/Http/Controllers"):
            class_type = "controller"
        elif rp.startswith("app/Models"):
            class_type = "model"
        elif rp.startswith("app/Services"):
            class_type = "service"
        elif rp.startswith("app/Jobs"):
            class_type = "job"
        elif rp.startswith("app/Http/Middleware"):
            class_type = "middleware"
        elif rp.startswith("tests/"):
            class_type = "test_class"
        class_id = graph.node(
            f"class:{full_name}",
            class_name,
            class_type,
            file=rp,
            line=text[: match.start()].count("\n") + 1,
            namespace=namespace,
        )
        graph.edge(file_id, class_id, "contains")
        if match.group(2):
            parent = match.group(2).split("\\")[-1]
            parent_id = graph.node(f"class-ref:{parent}", parent, "class_ref")
            graph.edge(class_id, parent_id, "extends")
        classes.append((class_name, class_id, text[: match.start()].count("\n") + 1))

    owner_id = classes[0][1] if classes else file_id
    for match in re.finditer(r"(?:public|protected|private)\s+function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(", text):
        method = match.group(1)
        method_id = graph.node(
            f"method:{rp}:{method}",
            method,
            "method",
            file=rp,
            line=text[: match.start()].count("\n") + 1,
        )
        graph.edge(owner_id, method_id, "defines")

        body_start = text.find("{", match.end())
        body = text[body_start : body_start + 2500] if body_start >= 0 else ""
        relation_match = re.search(
            r"return\s+\$this->(belongsTo|hasMany|hasOne|belongsToMany|morphMany|morphTo)\s*\(\s*([A-Za-z_\\][A-Za-z0-9_\\]*)::class",
            body,
        )
        if relation_match:
            target = relation_match.group(2).split("\\")[-1]
            target_id = graph.node(f"class-ref:{target}", target, "model_ref")
            graph.edge(method_id, target_id, relation_match.group(1), confidence="EXTRACTED")

        for view_match in re.finditer(r"view\s*\(\s*['\"]([^'\"]+)['\"]", body):
            view_name = view_match.group(1)
            view_id = graph.node(f"view:{view_name}", view_name, "view")
            graph.edge(method_id, view_id, "renders", evidence=f"{rp}:{text[: body_start + view_match.start()].count(chr(10)) + 1}")

        for model_match in re.finditer(r"\b(Phppos[A-Za-z0-9_]+|ItemVariation|AttributeValue|Attribute|Location|TransferQueue|PriceRule|PriceTier)::", body):
            model = model_match.group(1)
            model_id = graph.node(f"class-ref:{model}", model, "model_ref")
            graph.edge(method_id, model_id, "uses_model")


def parse_routes(graph: Graph, path: Path, text: str, file_id: str) -> None:
    rp = rel(path)
    route_re = re.compile(
        r"Route::(get|post|put|patch|delete|any)\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*\[([A-Za-z_\\][A-Za-z0-9_\\]*)::class\s*,\s*['\"]([^'\"]+)['\"]\]",
        re.IGNORECASE,
    )
    for match in route_re.finditer(text):
        verb, uri, controller, action = match.groups()
        route_id = graph.node(
            f"route:{verb.upper()}:{uri}:{controller}:{action}",
            f"{verb.upper()} {uri}",
            "route",
            file=rp,
            line=text[: match.start()].count("\n") + 1,
            action=action,
        )
        graph.edge(file_id, route_id, "declares")
        class_name = controller.split("\\")[-1]
        controller_id = graph.node(f"class-ref:{class_name}", class_name, "controller_ref")
        method_id = graph.node(f"method-ref:{class_name}.{action}", f"{class_name}@{action}", "controller_action")
        graph.edge(route_id, controller_id, "handled_by")
        graph.edge(route_id, method_id, "calls")


def parse_migration(graph: Graph, path: Path, text: str, file_id: str) -> None:
    rp = rel(path)
    for match in re.finditer(r"Schema::(create|table)\s*\(\s*['\"]([^'\"]+)['\"]", text):
        action, table = match.groups()
        table_id = graph.node(f"table:{table}", table, "table", file=rp, line=text[: match.start()].count("\n") + 1)
        graph.edge(file_id, table_id, "creates" if action == "create" else "alters")

        body_start = text.find("{", match.end())
        body_end = text.find("});", body_start)
        body = text[body_start:body_end] if body_start >= 0 and body_end >= 0 else ""
        for column_match in re.finditer(r"\$table->([A-Za-z0-9_]+)\s*\(\s*['\"]([^'\"]+)['\"]", body):
            column_type, column = column_match.groups()
            column_id = graph.node(f"column:{table}.{column}", column, "column", column_type=column_type)
            graph.edge(table_id, column_id, "has_column")
        for constrained_match in re.finditer(r"foreignId\s*\(\s*['\"]([^'\"]+)['\"].{0,120}?constrained\s*\(\s*['\"]?([^'\"\)]*)", body, flags=re.S):
            column, target = constrained_match.groups()
            target_table = target.strip() or re.sub(r"_id$", "s", column)
            target_id = graph.node(f"table:{target_table}", target_table, "table_ref")
            graph.edge(table_id, target_id, "foreign_key", column=column)


def parse_view(graph: Graph, path: Path, text: str, file_id: str) -> None:
    view_name = rel(path).removeprefix("resources/views/").removesuffix(".blade.php").replace("/", ".")
    view_id = graph.node(f"view:{view_name}", view_name, "view", file=rel(path))
    graph.edge(file_id, view_id, "defines")
    for match in re.finditer(r"route\s*\(\s*['\"]([^'\"]+)['\"]", text):
        route_name = match.group(1)
        route_id = graph.node(f"route-name:{route_name}", route_name, "route_name")
        graph.edge(view_id, route_id, "links_to_route")
    for match in re.finditer(r"@(include|extends|component)\s*\(\s*['\"]([^'\"]+)['\"]", text):
        target = match.group(2)
        target_id = graph.node(f"view:{target}", target, "view")
        graph.edge(view_id, target_id, match.group(1))


def parse_doc(graph: Graph, path: Path, text: str, file_id: str) -> None:
    rp = rel(path)
    for match in re.finditer(r"^(#{1,4})\s+(.+)$", text, re.M):
        title = match.group(2).strip()
        section_id = graph.node(
            f"doc-section:{rp}:{title}",
            title[:120],
            "doc_section",
            file=rp,
            line=text[: match.start()].count("\n") + 1,
            level=len(match.group(1)),
        )
        graph.edge(file_id, section_id, "contains")


def build_graph() -> Graph:
    graph = Graph()
    for path in iter_files():
        file_id = add_file_node(graph, path)
        text = read(path)
        if path.suffix == ".php" or path.name.endswith(".blade.php"):
            parse_php_file(graph, path, text, file_id)
        if rel(path).startswith("routes/"):
            parse_routes(graph, path, text, file_id)
        if rel(path).startswith("database/migrations"):
            parse_migration(graph, path, text, file_id)
        if rel(path).startswith("resources/views") and path.name.endswith(".blade.php"):
            parse_view(graph, path, text, file_id)
        if path.suffix == ".md":
            parse_doc(graph, path, text, file_id)
    return graph


def top_nodes(graph: Graph, limit: int = 15) -> list[tuple[dict, int]]:
    degree = Counter()
    for edge in graph.edges:
        degree[edge["source"]] += 1
        degree[edge["target"]] += 1
    return [(graph.nodes[node_id], count) for node_id, count in degree.most_common(limit)]


def write_report(graph: Graph) -> None:
    counts = Counter(node["type"] for node in graph.nodes.values())
    edge_counts = Counter(edge["type"] for edge in graph.edges)
    route_count = counts.get("route", 0)
    table_count = counts.get("table", 0) + counts.get("table_ref", 0)
    model_count = counts.get("model", 0)
    controller_count = counts.get("controller", 0)

    domain_hints = {
        "Sales/register": ["SalesController", "PhpposSale", "phppos_sales", "register"],
        "Purchasing/receiving": ["ReceivingController", "PhpposReceiving", "phppos_receivings"],
        "Inventory/items": ["ItemController", "ItemVariation", "phppos_items", "phppos_location_items"],
        "LAN transfer sync": ["LanController", "TransferController", "TransferQueue", "locations"],
        "VAT/reporting": ["ReportController", "phppos_vat_rates", "phppos_sales_items_taxes"],
        "People/access": ["EmployeeController", "CustomerController", "SupplierController", "phppos_people"],
    }

    lines = [
        "# Laravel POS Knowledge Graph Report",
        "",
        "Generated from local static analysis of routes, controllers, models, migrations, Blade views, tests, and project Markdown docs.",
        "",
        "## Snapshot",
        "",
        f"- Nodes: {len(graph.nodes):,}",
        f"- Edges: {len(graph.edges):,}",
        f"- Routes: {route_count:,}",
        f"- Controllers: {controller_count:,}",
        f"- Models: {model_count:,}",
        f"- Tables/table refs: {table_count:,}",
        f"- Views: {counts.get('view', 0):,}",
        "",
        "## Key Domains",
        "",
    ]
    node_labels = {node["label"] for node in graph.nodes.values()}
    node_ids = set(graph.nodes)
    for name, hints in domain_hints.items():
        present = []
        for hint in hints:
            if hint in node_labels or any(hint in node_id for node_id in node_ids):
                present.append(hint)
        lines.append(f"- {name}: {', '.join(present) if present else 'signals not found'}")

    lines.extend(["", "## Most Connected Concepts", ""])
    for node, degree in top_nodes(graph):
        location = f" ({node.get('file')}:{node.get('line')})" if node.get("file") and node.get("line") else ""
        lines.append(f"- {node['label']} [{node['type']}], degree {degree}{location}")

    lines.extend(["", "## Relationship Types", ""])
    for edge_type, count in edge_counts.most_common():
        lines.append(f"- {edge_type}: {count:,}")

    lines.extend(
        [
            "",
            "## Suggested Questions",
            "",
            "- Which routes touch sales, payments, register logs, and VAT tables?",
            "- What model relationships exist around item variations, suppliers, and location inventory?",
            "- Which controllers render each Blade view, and which views link back to route names?",
            "- Where does LAN transfer sync flow from web/API routes into jobs and queue tables?",
            "- Which active migrations still alter tables during the migration-cleanup phase?",
            "",
            "## Files",
            "",
            "- `graph.html`: interactive local browser graph.",
            "- `graph.json`: machine-readable graph with nodes, edges, files, lines, and confidence tags.",
            "- `GRAPH_REPORT.md`: this summary.",
        ]
    )
    (OUT / "GRAPH_REPORT.md").write_text("\n".join(lines) + "\n", encoding="utf-8")


def write_html(graph: Graph) -> None:
    data = {
        "nodes": list(graph.nodes.values()),
        "edges": graph.edges,
    }
    payload = json.dumps(data, ensure_ascii=False)
    html_doc = f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Laravel POS Knowledge Graph</title>
<style>
:root {{ color-scheme: light dark; --bg:#0f172a; --panel:#111827; --text:#e5e7eb; --muted:#94a3b8; --line:#334155; --accent:#38bdf8; }}
body {{ margin:0; font:14px/1.45 system-ui, -apple-system, Segoe UI, sans-serif; background:var(--bg); color:var(--text); }}
.app {{ display:grid; grid-template-columns: 320px 1fr; height:100vh; }}
aside {{ border-right:1px solid var(--line); background:var(--panel); overflow:auto; padding:16px; }}
main {{ position:relative; overflow:hidden; }}
h1 {{ font-size:18px; margin:0 0 12px; }}
.stats {{ display:grid; grid-template-columns:repeat(2,1fr); gap:8px; margin:12px 0; }}
.stat {{ border:1px solid var(--line); border-radius:8px; padding:8px; }}
.stat strong {{ display:block; font-size:20px; }}
input, select {{ width:100%; box-sizing:border-box; margin:6px 0 10px; padding:8px; border-radius:6px; border:1px solid var(--line); background:#020617; color:var(--text); }}
button {{ border:1px solid var(--line); background:#172554; color:var(--text); border-radius:6px; padding:8px 10px; cursor:pointer; }}
#list {{ display:grid; gap:6px; }}
.row {{ border:1px solid var(--line); border-radius:6px; padding:8px; cursor:pointer; }}
.row:hover {{ border-color:var(--accent); }}
.type {{ color:var(--muted); font-size:12px; }}
canvas {{ width:100%; height:100%; display:block; }}
.detail {{ position:absolute; right:16px; top:16px; width:min(420px, calc(100% - 32px)); max-height:calc(100% - 32px); overflow:auto; background:rgba(15,23,42,.94); border:1px solid var(--line); border-radius:8px; padding:14px; box-shadow:0 20px 50px rgba(0,0,0,.35); }}
.detail a {{ color:var(--accent); }}
@media (max-width: 800px) {{ .app {{ grid-template-columns:1fr; grid-template-rows:320px 1fr; }} aside {{ border-right:0; border-bottom:1px solid var(--line); }} }}
</style>
</head>
<body>
<div class="app">
<aside>
<h1>Laravel POS Knowledge Graph</h1>
<div class="stats">
<div class="stat"><span>Nodes</span><strong id="nodeCount"></strong></div>
<div class="stat"><span>Edges</span><strong id="edgeCount"></strong></div>
</div>
<label>Search</label>
<input id="search" placeholder="controller, table, route, view...">
<label>Type</label>
<select id="type"><option value="">All types</option></select>
<button id="fit">Refit Graph</button>
<p class="type">Tip: click a node or a list item to inspect links. This graph is generated locally from source files.</p>
<div id="list"></div>
</aside>
<main>
<canvas id="graph"></canvas>
<section class="detail" id="detail" hidden></section>
</main>
</div>
<script id="graph-data" type="application/json">{html.escape(payload)}</script>
<script>
const data = JSON.parse(document.getElementById('graph-data').textContent);
const nodes = data.nodes.map((n, i) => ({{...n, x: Math.random()*900+80, y: Math.random()*600+80, vx:0, vy:0, i}}));
const nodeById = new Map(nodes.map(n => [n.id, n]));
const edges = data.edges.map(e => ({{...e, sourceNode: nodeById.get(e.source), targetNode: nodeById.get(e.target)}})).filter(e => e.sourceNode && e.targetNode);
const colors = {{route:'#38bdf8', controller:'#f97316', model:'#22c55e', table:'#a78bfa', view:'#facc15', method:'#fb7185', doc_section:'#cbd5e1'}};
const canvas = document.getElementById('graph');
const ctx = canvas.getContext('2d');
const search = document.getElementById('search');
const type = document.getElementById('type');
const list = document.getElementById('list');
const detail = document.getElementById('detail');
document.getElementById('nodeCount').textContent = nodes.length.toLocaleString();
document.getElementById('edgeCount').textContent = edges.length.toLocaleString();
for (const t of [...new Set(nodes.map(n => n.type))].sort()) {{
  const option = document.createElement('option'); option.value = t; option.textContent = t; type.appendChild(option);
}}
let selected = null, scale = 1, ox = 0, oy = 0;
function resize() {{ canvas.width = canvas.clientWidth * devicePixelRatio; canvas.height = canvas.clientHeight * devicePixelRatio; }}
addEventListener('resize', resize); resize();
function filteredNodes() {{
  const q = search.value.toLowerCase();
  return nodes.filter(n => (!type.value || n.type === type.value) && (!q || (n.label + ' ' + n.id + ' ' + (n.file||'')).toLowerCase().includes(q))).slice(0, 350);
}}
function updateList() {{
  const visible = filteredNodes().slice(0, 80);
  list.innerHTML = '';
  for (const n of visible) {{
    const row = document.createElement('div'); row.className = 'row';
    row.innerHTML = `<strong>${{escapeHtml(n.label)}}</strong><div class="type">${{escapeHtml(n.type)}}${{n.file ? ' · '+escapeHtml(n.file) : ''}}</div>`;
    row.onclick = () => selectNode(n);
    list.appendChild(row);
  }}
}}
function escapeHtml(s) {{ return String(s).replace(/[&<>"']/g, c => ({{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}}[c])); }}
function selectNode(n) {{
  selected = n;
  const links = edges.filter(e => e.source === n.id || e.target === n.id).slice(0, 80);
  detail.hidden = false;
  detail.innerHTML = `<h2>${{escapeHtml(n.label)}}</h2><p class="type">${{escapeHtml(n.type)}}${{n.file ? ' · '+escapeHtml(n.file) : ''}}${{n.line ? ':'+n.line : ''}}</p>
    <pre>${{escapeHtml(JSON.stringify(n, null, 2))}}</pre>
    <h3>Links</h3>${{links.map(e => `<div class="row"><span class="type">${{escapeHtml(e.type)}}</span><br>${{escapeHtml(nodeById.get(e.source)?.label || e.source)}} -> ${{escapeHtml(nodeById.get(e.target)?.label || e.target)}}</div>`).join('')}}`;
}}
function tick() {{
  const visible = new Set(filteredNodes().map(n => n.id));
  const active = nodes.filter(n => visible.has(n.id));
  for (const e of edges) {{
    if (!visible.has(e.source) || !visible.has(e.target)) continue;
    const a=e.sourceNode,b=e.targetNode, dx=b.x-a.x, dy=b.y-a.y, dist=Math.max(1, Math.hypot(dx,dy));
    const force=(dist-140)*0.0008; const fx=dx*force, fy=dy*force;
    a.vx += fx; a.vy += fy; b.vx -= fx; b.vy -= fy;
  }}
  for (let i=0;i<active.length;i++) for (let j=i+1;j<Math.min(active.length,i+40);j++) {{
    const a=active[i], b=active[j], dx=b.x-a.x, dy=b.y-a.y, d2=Math.max(25, dx*dx+dy*dy), f=80/d2;
    a.vx -= dx*f; a.vy -= dy*f; b.vx += dx*f; b.vy += dy*f;
  }}
  for (const n of active) {{ n.vx += (canvas.width/devicePixelRatio/2 - n.x)*0.0007; n.vy += (canvas.height/devicePixelRatio/2 - n.y)*0.0007; n.x += n.vx; n.y += n.vy; n.vx *= .86; n.vy *= .86; }}
}}
function draw() {{
  tick();
  const w=canvas.width, h=canvas.height; ctx.clearRect(0,0,w,h); ctx.save(); ctx.scale(devicePixelRatio, devicePixelRatio); ctx.translate(ox, oy); ctx.scale(scale, scale);
  const visible = new Set(filteredNodes().map(n => n.id));
  ctx.lineWidth = 1; ctx.globalAlpha = .22; ctx.strokeStyle = '#94a3b8';
  for (const e of edges) if (visible.has(e.source) && visible.has(e.target)) {{ ctx.beginPath(); ctx.moveTo(e.sourceNode.x,e.sourceNode.y); ctx.lineTo(e.targetNode.x,e.targetNode.y); ctx.stroke(); }}
  ctx.globalAlpha = 1;
  for (const n of nodes) if (visible.has(n.id)) {{
    const r = selected?.id === n.id ? 8 : 5;
    ctx.fillStyle = colors[n.type] || '#64748b'; ctx.beginPath(); ctx.arc(n.x,n.y,r,0,Math.PI*2); ctx.fill();
    if (selected?.id === n.id || ['controller','model','table','route'].includes(n.type)) {{ ctx.fillStyle='#e5e7eb'; ctx.font='12px system-ui'; ctx.fillText(n.label.slice(0,34), n.x+9, n.y+4); }}
  }}
  ctx.restore(); requestAnimationFrame(draw);
}}
canvas.addEventListener('click', ev => {{
  const rect=canvas.getBoundingClientRect(), x=(ev.clientX-rect.left-ox)/scale, y=(ev.clientY-rect.top-oy)/scale;
  let best=null, bd=Infinity; for (const n of filteredNodes()) {{ const d=Math.hypot(n.x-x,n.y-y); if (d<bd) {{ best=n; bd=d; }} }}
  if (best && bd<18) selectNode(best);
}});
document.getElementById('fit').onclick = () => {{ for (const n of nodes) {{ n.x=Math.random()*canvas.clientWidth; n.y=Math.random()*canvas.clientHeight; }} }};
search.oninput = updateList; type.onchange = updateList; updateList(); draw();
</script>
</body>
</html>"""
    (OUT / "graph.html").write_text(html_doc, encoding="utf-8")


def main() -> None:
    OUT.mkdir(exist_ok=True)
    graph = build_graph()
    graph_payload = {
        "metadata": {
            "project": ROOT.name,
            "root": str(ROOT),
            "generator": "tools/build_knowledge_base.py",
            "style": "graphify-compatible local knowledge graph",
        },
        "nodes": list(graph.nodes.values()),
        "edges": graph.edges,
    }
    (OUT / "graph.json").write_text(json.dumps(graph_payload, indent=2, ensure_ascii=False), encoding="utf-8")
    write_report(graph)
    write_html(graph)
    print(f"Wrote {OUT / 'graph.json'}")
    print(f"Wrote {OUT / 'GRAPH_REPORT.md'}")
    print(f"Wrote {OUT / 'graph.html'}")
    print(f"Nodes: {len(graph.nodes)}")
    print(f"Edges: {len(graph.edges)}")


if __name__ == "__main__":
    main()
