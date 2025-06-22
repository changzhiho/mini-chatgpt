<?php

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;

class ConversationService
{
    public function createConversation(string $model): Conversation
    {
        return Conversation::create([
            'user_id' => Auth::id(),
            'title' => 'Nouvelle conversation',
            'model' => $model
        ]);
    }

    public function deleteConversation(int $id): ?Conversation
    {
        $conversation = Conversation::where('user_id', Auth::id())->findOrFail($id);
        $conversation->delete();

        return Conversation::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->first();
    }

    public function shareConversation(int $id): array
    {
        $conversation = Conversation::find($id);

        if ($conversation->user_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à partager cette conversation.');
        }

        return [
            'share_url' => route('conversation.share', $conversation->uuid),
            'share_success' => true
        ];
    }

    public function getUserConversations()
    {
        return Conversation::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->get();
    }
}
