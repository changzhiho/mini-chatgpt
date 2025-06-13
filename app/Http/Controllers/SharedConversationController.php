<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SharedConversationController extends Controller
{
    public function show(Conversation $conversation)
    {
        // Charger la conversation avec ses messages
        $conversation->load(['messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }, 'user:id,name']);

        return Inertia::render('SharedConversation/Show', [
            'conversation' => $conversation,
            'messages' => $conversation->messages,
            'owner' => $conversation->user,
        ]);
    }
}
