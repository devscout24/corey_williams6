<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\TransferQueue;
use Illuminate\View\View;

class LanStatusController extends Controller
{
    public function index(): View
    {
        $locations = Location::orderByDesc('is_self')
            ->orderBy('name')
            ->get();

        $transfers = TransferQueue::with('destination')
            ->latest()
            ->take(50)
            ->get();

        return view('lan.locations', compact('locations', 'transfers'));
    }
}
