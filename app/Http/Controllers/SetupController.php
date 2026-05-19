<?php

namespace App\Http\Controllers;

use App\Models\PhpposAppConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\PhpposEmployee;
use App\Models\PhpposPerson;

class SetupController extends Controller
{
    public function index()
    {
        return view('setup');
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'email' => 'required|string|email|max:255',
        ]);

        // Run migrations and seed the database
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        // Create the admin user
        $person = PhpposPerson::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => $request->email,
        ]);

        PhpposEmployee::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'person_id' => $person->person_id,
        ]);

        // Mark setup as complete
        PhpposAppConfig::create(['key' => 'setup_complete', 'value' => '1']);


        return redirect('/');
    }
}
