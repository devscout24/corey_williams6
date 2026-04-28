<?php

namespace App\Http\Controllers;

use App\Models\PhpposTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TagController extends Controller
{
    /**
     * Display a listing of tags.
     */
    public function index(): View
    {
        $tags = DB::table('phppos_tags')
            ->where('deleted', 0)
            ->orderBy('name')
            ->paginate(20);

        return view('tags.index', compact('tags'));
    }

    /**
     * Get tag list for AJAX/select dropdowns.
     */
    public function tagList(): JsonResponse
    {
        $tags = DB::table('phppos_tags')
            ->where('deleted', 0)
            ->orderBy('name')
            ->get();

        return response()->json($tags);
    }

    /**
     * Show the form for creating a new tag.
     */
    public function create(): View
    {
        return view('tags.form', ['tag' => null]);
    }

    /**
     * Store a newly created tag in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tag_name' => ['required', 'string', 'max:255', 'unique:phppos_tags,name'],
        ]);

        DB::table('phppos_tags')->insert([
            'name' => $data['tag_name'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('tags.index')->with('status', 'Tag successfully added: ' . $data['tag_name']);
    }

    /**
     * Show the form for editing the specified tag.
     */
    public function edit(int $id): View
    {
        $tag = DB::table('phppos_tags')->where('id', $id)->firstOrFail();
        return view('tags.form', ['tag' => $tag]);
    }

    /**
     * Update the specified tag in storage.
     */
    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'tag_name' => ['required', 'string', 'max:255', 'unique:phppos_tags,name,' . $id],
        ]);

        DB::table('phppos_tags')->where('id', $id)->update([
            'name' => $data['tag_name'],
            'updated_at' => now(),
        ]);

        return redirect()->route('tags.index')->with('status', 'Tag successfully updated: ' . $data['tag_name']);
    }

    /**
     * Remove the specified tag from storage (soft delete).
     */
    public function destroy(int $id)
    {
        $tag = DB::table('phppos_tags')->where('id', $id)->first();
        DB::table('phppos_tags')->where('id', $id)->update(['deleted' => 1]);

        return redirect()->route('tags.index')->with('status', 'Tag deleted successfully: ' . ($tag->name ?? ''));
    }

    
}
