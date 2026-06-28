<?php

namespace App\Http\Controllers;

use App\Models\PhpposCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = PhpposCategory::query()
            ->where('deleted', 0)
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        PhpposCategory::query()->create([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
            'deleted' => 0,
        ]);

        return back()->with('status', 'Category saved.');
    }

    public function update(Request $request, int $categoryId): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        PhpposCategory::query()->where('id', $categoryId)->update([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return back()->with('status', 'Category updated.');
    }

    public function destroy(int $categoryId): RedirectResponse
    {
        PhpposCategory::query()->where('id', $categoryId)->update(['deleted' => 1]);

        return back()->with('status', 'Category archived.');
    }

    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');

        $categories = PhpposCategory::query()
            ->where('deleted', 0)
            ->orderBy('name')
            ->get();

        $nameMap = $categories->pluck('name', 'id');

        $rows = [];
        foreach ($categories as $cat) {
            $rows[] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug ?? '',
                'parent_name' => $cat->parent_id ? ($nameMap[$cat->parent_id] ?? '') : '',
                'hide_from_grid' => $cat->hide_from_grid ? 1 : 0,
            ];
        }

        $columnLabels = ['ID', 'Name', 'Slug', 'Parent Category', 'Hide From Grid'];

        if ($format === 'xls') {
            $html = '<html><head><meta charset="UTF-8"><title>Categories Export</title>';
            $html .= '<style>table{border-collapse:collapse;width:100%;font-family:Arial,sans-serif;font-size:11pt;}th,td{border:1px solid #ccc;padding:4px 6px;text-align:left;}th{background:#f0f0f0;font-weight:bold;}</style>';
            $html .= '</head><body><h2>Categories</h2>';
            $html .= '<table><thead><tr>';
            foreach ($columnLabels as $label) {
                $html .= '<th>'.htmlspecialchars($label).'</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                $html .= '<td>'.htmlspecialchars((string) $row['id']).'</td>';
                $html .= '<td>'.htmlspecialchars($row['name']).'</td>';
                $html .= '<td>'.htmlspecialchars($row['slug']).'</td>';
                $html .= '<td>'.htmlspecialchars($row['parent_name']).'</td>';
                $html .= '<td>'.$row['hide_from_grid'].'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></body></html>';

            return response($html, 200, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="categories-export.xls"',
            ]);
        }

        $callback = function () use ($columnLabels, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columnLabels);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['id'],
                    $row['name'],
                    $row['slug'],
                    $row['parent_name'],
                    $row['hide_from_grid'],
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="categories-export.csv"',
        ]);
    }

    public function importForm(): View
    {
        return view('categories.import');
    }

    public function import(Request $request): RedirectResponse {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv,txt,xls,html'],
        ]);

        $file = $request->file('import_file');
        $ext  = strtolower($file->getClientOriginalExtension());

        $rows = ($ext === 'xls' || $ext === 'html')
            ? $this->parseXls($file->getRealPath())
            : $this->parseCsv($file->getRealPath());

        if (empty($rows)) {
            return back()->withErrors(['import_file' => 'The file is empty or has no valid data rows.']);
        }

        $rows = $this->sortImportRows($rows);

        // Load existing categories.
        // Lookup maps:
        // 1. $byComposite["name|parent_id"] => category data  (exact match)
        // 2. $byName["name"][]              => [category data, ...]  (name-only)
        // 3. $bySlug["slug"]                => category data  (slug lookup)
        $byComposite = [];
        $byName      = [];
        $bySlug      = [];

        $existingCategories = PhpposCategory::query()->where('deleted', 0)->get();

        foreach ($existingCategories as $cat) {
            $nameKey      = strtolower($cat->name);
            $compositeKey = $nameKey . '|' . ($cat->parent_id ?? '');

            $entry = [
                'id'             => $cat->id,
                'parent_id'      => $cat->parent_id,
                'slug'           => $cat->slug ?? '',
                'hide_from_grid' => (int) $cat->hide_from_grid,
            ];

            $byComposite[$compositeKey]  = $entry;
            $byName[$nameKey][]          = $entry;
            if (($cat->slug ?? '') !== '') {
                $bySlug[strtolower($cat->slug)] = $entry;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $name         = trim($row['name'] ?? $row['Name'] ?? '');
            $slug         = strtolower(trim($row['slug'] ?? $row['Slug'] ?? ''));
            $hideFromGrid = (int) ($row['hide_from_grid'] ?? $row['Hide From Grid'] ?? 0);

            if ($name === '') {
                $skipped++;
                continue;
            }

            // Auto-generate slug from name if not provided
            if ($slug === '') {
                $slug = \Illuminate\Support\Str::slug($name);
            }

            // Resolve parent from slug path (everything before last /)
            $parentId = null;
            $slugParts = explode('/', $slug);
            if (count($slugParts) > 1) {
                array_pop($slugParts);
                $parentSlug = implode('/', $slugParts);
                $parentEntry = $bySlug[$parentSlug] ?? null;

                if ($parentEntry) {
                    $parentId = $parentEntry['id'];
                }
                // If parent slug not found → stays null (top-level)
            }

            $nameKey      = strtolower($name);
            $compositeKey = $nameKey . '|' . ($parentId ?? '');
            $existing     = $byComposite[$compositeKey] ?? null;

            // Also try slug match for updates
            if (!$existing && isset($bySlug[$slug])) {
                $existing = $bySlug[$slug];
            }

            if ($existing) {
                if ($existing['hide_from_grid'] !== $hideFromGrid) {
                    PhpposCategory::query()->where('id', $existing['id'])->update([
                        'hide_from_grid' => $hideFromGrid,
                    ]);

                    // Keep maps in sync
                    $byComposite[$compositeKey]['hide_from_grid'] = $hideFromGrid;
                    if (isset($bySlug[$slug])) {
                        $bySlug[$slug]['hide_from_grid'] = $hideFromGrid;
                    }
                    foreach ($byName[$nameKey] as &$n) {
                        if ($n['id'] === $existing['id']) {
                            $n['hide_from_grid'] = $hideFromGrid;
                            break;
                        }
                    }
                    unset($n);

                    $updated++;
                }
            } else {
                $newCat = PhpposCategory::query()->create([
                    'name'           => $name,
                    'parent_id'      => $parentId,
                    'deleted'        => 0,
                    'hide_from_grid' => $hideFromGrid,
                ]);

                $entry = [
                    'id'             => $newCat->id,
                    'parent_id'      => $parentId,
                    'slug'           => $newCat->slug ?? '',
                    'hide_from_grid' => $hideFromGrid,
                ];

                // Add to all maps so later rows can resolve this as a parent
                $byComposite[$compositeKey] = $entry;
                $byName[$nameKey][]         = $entry;
                $bySlug[$slug]              = $entry;

                $created++;
            }
        }

        $message = "Import complete. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.";

        return back()->with('status', $message);
    }
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);

            return [];
        }

        $headers = array_map('strtolower', array_map('trim', $headers));

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }
        fclose($handle);

        return $rows;
    }

    private function parseXls(string $path): array
    {
        $content = file_get_contents($path);
        if (! $content) {
            return [];
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        libxml_clear_errors();

        $tables = $dom->getElementsByTagName('table');
        if ($tables->length === 0) {
            return [];
        }

        $table = $tables->item(0);
        $rows = [];

        $trElements = $table->getElementsByTagName('tr');
        if ($trElements->length < 2) {
            return [];
        }

        $headerCells = $trElements->item(0)->getElementsByTagName('td');
        if ($headerCells->length === 0) {
            $headerCells = $trElements->item(0)->getElementsByTagName('th');
        }

        $headers = [];
        for ($i = 0; $i < $headerCells->length; $i++) {
            $headers[] = strtolower(trim($headerCells->item($i)->textContent));
        }

        for ($i = 1; $i < $trElements->length; $i++) {
            $cells = $trElements->item($i)->getElementsByTagName('td');
            if ($cells->length === count($headers)) {
                $row = [];
                for ($j = 0; $j < $cells->length; $j++) {
                    $row[$headers[$j]] = trim($cells->item($j)->textContent);
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function sortImportRows(array $rows): array
    {
        $depths = [];
        foreach ($rows as $i => $row) {
            $slug = strtolower(trim($row['slug'] ?? $row['Slug'] ?? ''));
            if ($slug === '') {
                $slug = \Illuminate\Support\Str::slug(strtolower(trim($row['name'] ?? $row['Name'] ?? '')));
            }
            $depths[$i] = substr_count($slug, '/');
        }

        $indices = range(0, count($rows) - 1);
        usort($indices, fn($a, $b) => $depths[$a] <=> $depths[$b]);

        return array_map(fn($i) => $rows[$i], $indices);
    }
}
