<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use App\Services\ConversationService;
use App\Services\CustomCommandService;
use App\Services\TitleGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Conversation;
use App\Models\Message;

class AskController extends Controller
{
    public function __construct(
        private ConversationService $conversationService,
        private CustomCommandService $customCommandService,
        private TitleGeneratorService $titleGeneratorService
    ) {}

    public function index(Request $request)
    {
        $models = (new ChatService())->getModels();
        $selectedModel = Auth::user()->preferred_model ?? ChatService::DEFAULT_MODEL;
        $conversations = $this->conversationService->getUserConversations();
        $selectedConversationId = session('selectedConversationId') ?? $request->get('conversation');

        return Inertia::render('Ask/Index', [
            'models' => $models,
            'selectedModel' => $selectedModel,
            'conversations' => $conversations,
            'selectedConversationId' => $selectedConversationId,
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
            $conversation = $this->conversationService->createConversation($request->model);
        }

        $user->update(['preferred_model' => $request->model]);

        // Traiter les commandes personnalisées
        $processedMessage = $this->customCommandService->processCommands($request->message, $user);

        // Sauvegarder le message utilisateur
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $request->message
        ]);

        // Préparer les messages pour l'IA
        $finalMessages = $this->prepareMessagesForAI($conversation, $user, $processedMessage, $request->message);

        return response()->stream(function () use ($conversation, $finalMessages, $request, $user) {
            $fullResponse = '';

            (new ChatService())->sendMessageStreamed(
                messages: $finalMessages,
                model: $request->model,
                onChunk: function ($content) use (&$fullResponse) {
                    $fullResponse .= $content;
                    echo $content;
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                    usleep(10000);
                }
            );

            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $fullResponse
            ]);

            if ($conversation->messages()->count() === 2 && $conversation->title === 'Nouvelle conversation') {
                $this->titleGeneratorService->generateTitle($conversation, $request->message, $fullResponse);
            }

            $conversation->touch();
        }, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    public function createConversation(Request $request)
    {
        $conversation = $this->conversationService->createConversation(
            $request->model ?? Auth::user()->preferred_model ?? ChatService::DEFAULT_MODEL
        );

        return redirect()->route('ask.index')->with([
            'selectedConversationId' => $conversation->id,
            'selectedConversationUuid' => $conversation->uuid,
            'shouldFocusInput' => true,
            'newConversationCreated' => true
        ]);
    }

    public function deleteConversation($id)
    {
        $nextConversation = $this->conversationService->deleteConversation($id);

        return redirect()->route('ask.index')->with([
            'selectedConversationId' => $nextConversation?->id,
            'shouldFocusInput' => true
        ]);
    }

    public function share($id)
    {
        $shareData = $this->conversationService->shareConversation($id);
        return redirect()->back()->with($shareData);
    }

    private function prepareMessagesForAI($conversation, $user, $processedMessage, $originalMessage)
    {
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

        $userMessages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) use ($processedMessage, $originalMessage) {
                if ($message->content === $originalMessage && $message->role === 'user') {
                    return [
                        'role' => $message->role,
                        'content' => $processedMessage
                    ];
                }
                return [
                    'role' => $message->role,
                    'content' => $message->content
                ];
            })
            ->toArray();

        return array_merge($systemMessages, $userMessages);
    }
}
