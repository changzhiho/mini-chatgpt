<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InstructionsController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        return Inertia::render('Instructions/Edit', [
            'instructions_about' => $user->instructions_about,
            'instructions_how' => $user->instructions_how,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'instructions_about' => 'nullable|string|max:2000',
            'instructions_how' => 'nullable|string|max:2000',
        ]);

        $user = Auth::user();
        $user->update([
            'instructions_about' => $request->instructions_about,
            'instructions_how' => $request->instructions_how,
        ]);

        return redirect()->route('ask.index')->with('success', 'Instructions mises à jour avec succès !');
    }
}
