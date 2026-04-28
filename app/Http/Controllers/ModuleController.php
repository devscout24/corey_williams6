<?php

namespace App\Http\Controllers;

use App\Models\PhpposModule;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(): View
    {
        $modules = PhpposModule::query()
            ->whereIn('module_id', ['locations', 'contacts', 'items', 'receivings', 'sales', 'messages', 'config', 'employees'])
            ->with('submodules')
            ->orderBy('sort')
            ->get();

        return view('modules.index', compact('modules'));
    }
}
