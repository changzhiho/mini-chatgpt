<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CommandsController extends Controller
{
    public function edit()
    {
        return Inertia::render('Commands/Edit', [
            'custom_commands' => Auth::user()->custom_commands,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'custom_commands' => 'nullable|string|max:5000',
        ]);

        $user = Auth::user();
        $user->custom_commands = $request->custom_commands;
        $user->save();

        return redirect()->route('ask.index')->with('success', 'Commandes personnalisées mises à jour !');
    }
}
