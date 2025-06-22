<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InstructionsController extends Controller
{
    public function edit(Request $request)
    {
        $user = Auth::user();
        $fromConversation = $request->get('from_conversation');

        return Inertia::render('Instructions/Edit', [
            'instructions_about' => $user->instructions_about,
            'instructions_how' => $user->instructions_how,
            'from_conversation' => $fromConversation,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'instructions_about' => 'nullable|string|max:2000',
            'instructions_how' => 'nullable|string|max:2000',
            'from_conversation' => 'nullable|string',
        ]);

        $user = Auth::user();
        $user->update([
            'instructions_about' => $request->instructions_about,
            'instructions_how' => $request->instructions_how,
        ]);

        //Redirection intelligente
        if ($request->from_conversation) {
            return redirect()->route('ask.index')->with([
                'selectedConversationId' => $request->from_conversation,
                'shouldFocusInput' => true,
                'success' => 'Instructions mises à jour avec succès !',
            ]);
        }

        return redirect()->route('ask.index')->with('success', 'Instructions mises à jour avec succès !');
    }
}
