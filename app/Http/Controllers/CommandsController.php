<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CommandsController extends Controller
{
    public function edit(Request $request)
    {
        $fromConversation = $request->get('from_conversation');

        return Inertia::render('Commands/Edit', [
            'custom_commands' => Auth::user()->custom_commands,
            'from_conversation' => $fromConversation,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'custom_commands' => 'nullable|string|max:5000',
            'from_conversation' => 'nullable|string',
        ]);

        $user = Auth::user();
        $user->custom_commands = $request->custom_commands;
        $user->save();

        //Redirection intelligente
        if ($request->from_conversation) {
            return redirect()->route('ask.index')->with([
                'selectedConversationId' => $request->from_conversation,
                'shouldFocusInput' => true,
                'success' => 'Commandes personnalisées mises à jour !',
            ]);
        }

        return redirect()->route('ask.index')->with('success', 'Commandes personnalisées mises à jour !');
    }
}
