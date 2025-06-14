<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Conversation;
use App\Models\Message;
use League\Uri\IPv4\Converter;

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
            // Préparer le message système avec les instructions personnalisées
            $systemMessages = [];
            if ($user->instructions_about || $user->instructions_how) {
                $instructions = "Instructions personnalisées de l'utilisateur :\n";
                if ($user->instructions_about) {
                    $instructions .= "À propos de l'utilisateur : " . $user->instructions_about . "\n";
                }
                if ($user->instructions_how) {
                    $instructions .= "Style de réponse souhaité : " . $user->instructions_how . "\n";
                }
                $systemMessages[] = [
                    'role' => 'system',
                    'content' => $instructions
                ];
            }

            // Préparer tous les messages de la conversation pour l'API
            $userMessages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) {
                    return [
                        'role' => $message->role,
                        'content' => $message->content
                    ];
                })
                ->toArray();

            // Fusionner instructions système et messages de la conversation
            $finalMessages = array_merge($systemMessages, $userMessages);

            // Utiliser votre ChatService existant
            $response = (new ChatService())->sendMessage(
                messages: $finalMessages,
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
                $this->generateConversationTitle($conversation, $request->message, $response);
            }

            $conversation->touch();

            return redirect()->back()->with([
                'selectedConversationId' => $conversation->id,
                'shouldFocusInput' => true
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }

    public function createConversation(Request $request)
    {
        $conversation = Conversation::create([
            'user_id' => Auth::id(),
            'title' => 'Nouvelle conversation',
            'model' => $request->model ?? Auth::user()->preferred_model ?? ChatService::DEFAULT_MODEL
        ]);

        return redirect()->route('ask.index')->with([
            'selectedConversationId' => $conversation->id,
            'selectedConversationUuid' => $conversation->uuid,
            'shouldFocusInput' => true,
            'newConversationCreated' => true
        ]);
    }

    public function deleteConversation($id)
    {
        $conversation = Conversation::where('user_id', Auth::id())->findOrFail($id);
        $conversation->delete();

        // Sélectionner la première conversation restante
        $nextConversation = Conversation::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->first();

        return redirect()->route('ask.index')->with([
            'selectedConversationId' => $nextConversation?->id,
            'shouldFocusInput' => true
        ]);
    }

    private function generateConversationTitle($conversation, $userMessage, $aiResponse)
    {
        try {
            $titleMessages = [
                [
                    'role' => 'system',
                    'content' => 'Génère un titre court et descriptif (maximum 50 caractères) pour cette conversation basé sur la question de l\'utilisateur et la réponse de l\'IA. Le titre doit résumer le sujet principal abordé. Réponds uniquement avec le titre, sans guillemets ni ponctuation supplémentaire.'
                ],
                [
                    'role' => 'user',
                    $content = 'content' => $userMessage
                ],
                [
                    'role' => 'assistant',
                    'content' => $aiResponse
                ],
                [
                    'role' => 'user',
                    'content' => 'Génère maintenant un titre pour cette conversation:'
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

    public function share($id)
    {
        $conversation = Conversation::find($id);
        if ($conversation->user_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à partager cette conversation.');
        }

        return redirect()->back()->with([
            'share_url' => route('conversation.share', $conversation->uuid),
            'share_success' => true
        ]);
    }
}
