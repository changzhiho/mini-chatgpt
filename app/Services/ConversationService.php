<?php

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;

//Service de gestion des conversations utilisateur
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

    //Suppression sécurisée d'une conversation avec sélection automatique de la suivante
    public function deleteConversation(int $id): ?Conversation
    {
        $conversation = Conversation::where('user_id', Auth::id())->findOrFail($id);
        $conversation->delete();

        return Conversation::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->first();
    }


    //Génération de lien de partage public avec vérification de propriété
    public function shareConversation(int $id): array
    {
        $conversation = Conversation::find($id);

        // Contrôle d'accès : seul le propriétaire peut partager
        if ($conversation->user_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à partager cette conversation.');
        }

        return [
            'share_url' => route('conversation.share', $conversation->uuid),
            'share_success' => true
        ];
    }

    //Récupération des conversations utilisateur avec messages préchargés
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
