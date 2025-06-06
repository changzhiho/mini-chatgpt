<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Conversation;
use App\Models\Message;

class AskController extends Controller
{
    public function index()
    {
        $models = (new ChatService())->getModels();
        $selectedModel = Auth::user()->preferred_model ?? ChatService::DEFAULT_MODEL;

        $conversations = Conversation::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->get();

        return Inertia::render('Ask/Index', [
            'models' => $models,
            'selectedModel' => $selectedModel,
            'conversations' => $conversations,
        ]);
    }

    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'required|string',
            'conversation_id' => 'nullable|exists:conversations,id',
        ]);

        $user = Auth::user();

        // Créer ou récupérer la conversation
        if ($request->conversation_id) {
            $conversation = Conversation::findOrFail($request->conversation_id);
            $conversation->update(['model' => $request->model]);
        } else {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'title' => 'Nouvelle conversation',
                'model' => $request->model
            ]);
        }

        // Sauvegarder le modèle préféré de l'utilisateur
        $user->update(['preferred_model' => $request->model]);

        // Sauvegarder le message utilisateur
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $request->message
        ]);

        try {
            // Préparer tous les messages de la conversation pour l'API
            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) {
                    return [
                        'role' => $message->role,
                        'content' => $message->content
                    ];
                })
                ->toArray();

            // Utiliser votre ChatService existant
            $response = (new ChatService())->sendMessage(
                messages: $messages,
                model: $request->model
            );

            // Sauvegarder la réponse de l'IA
            $aiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response
            ]);

            // Générer un titre si c'est le premier échange
            if ($conversation->messages()->count() === 2 && $conversation->title === 'Nouvelle conversation') {
                $this->generateConversationTitle($conversation, $request->message);
            }

            $conversation->touch();

            // Recharger toutes les conversations avec les messages mis à jour
            $updatedConversations = Conversation::where('user_id', Auth::id())
                ->orderBy('updated_at', 'desc')
                ->with(['messages' => function ($query) {
                    $query->orderBy('created_at', 'asc');
                }])
                ->get();

            // Retourner avec preserveScroll et les données complètes mises à jour
            return Inertia::render('Ask/Index', [
                'models' => (new ChatService())->getModels(),
                'selectedModel' => $user->preferred_model,
                'conversations' => $updatedConversations,
                'selectedConversationId' => $conversation->id,
                'shouldFocusInput' => true, // Signal pour focus automatique
                'flash' => [
                    'success' => true,
                    'message' => 'Message envoyé avec succès'
                ]
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'message' => 'Erreur: ' . $e->getMessage()
            ])->with('preserveScroll', true);
        }
    }

    private function generateConversationTitle($conversation, $firstMessage)
    {
        try {
            $titleMessages = [
                [
                    'role' => 'system',
                    'content' => 'Génère un titre court et descriptif (maximum 50 caractères) pour cette conversation basé sur le premier message de l\'utilisateur. Réponds uniquement avec le titre, sans guillemets ni ponctuation supplémentaire.'
                ],
                [
                    'role' => 'user',
                    'content' => $firstMessage
                ]
            ];

            $title = (new ChatService())->sendMessage(
                messages: $titleMessages,
                model: ChatService::DEFAULT_MODEL
            );

            $conversation->update(['title' => trim($title)]);
        } catch (\Exception $e) {
            // En cas d'erreur, garder le titre par défaut
        }
    }

    public function createConversation(Request $request)
    {
        $conversation = Conversation::create([
            'user_id' => Auth::id(),
            'title' => 'Nouvelle conversation',
            'model' => $request->model ?? Auth::user()->preferred_model ?? ChatService::DEFAULT_MODEL
        ]);

        // Recharger toutes les conversations avec la nouvelle
        $updatedConversations = Conversation::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->get();

        // Retourner avec la nouvelle conversation sélectionnée et focus automatique
        return Inertia::render('Ask/Index', [
            'models' => (new ChatService())->getModels(),
            'selectedModel' => Auth::user()->preferred_model ?? ChatService::DEFAULT_MODEL,
            'conversations' => $updatedConversations,
            'selectedConversationId' => $conversation->id,
            'shouldFocusInput' => true, // Focus automatique pour nouvelle conversation
            'flash' => [
                'success' => true,
                'message' => 'Nouvelle conversation créée'
            ]
        ]);
    }

    public function deleteConversation($id)
    {
        $conversation = Conversation::where('user_id', Auth::id())->findOrFail($id);
        $conversation->delete();

        return redirect()->back();
    }
}
