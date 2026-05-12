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
}
