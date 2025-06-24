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

    /**
     * Traitement des messages avec streaming et génération automatique de titre
     */
    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'required|string',
            'conversation_id' => 'nullable|exists:conversations,id',
        ]);

        $user = Auth::user();

        // Gestion conversation existante ou création automatique
        if ($request->conversation_id) {
            $conversation = Conversation::findOrFail($request->conversation_id);
            $conversation->update(['model' => $request->model]);
        } else {
            $conversation = $this->conversationService->createConversation($request->model);
        }

        $user->update(['preferred_model' => $request->model]);

        $processedMessage = $this->customCommandService->processCommands($request->message, $user);

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $request->message
        ]);

        $finalMessages = $this->prepareMessagesForAI($conversation, $user, $processedMessage, $request->message);

        $isFirstMessage = $conversation->messages()->count() === 1;

        return response()->stream(function () use ($conversation, $finalMessages, $request, $user, $isFirstMessage, $processedMessage) {
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

            // Sauvegarde du message IA complet
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $fullResponse
            ]);

            // Génération et streaming du titre après le premier échange
            if ($isFirstMessage && $conversation->title === 'Nouvelle conversation') {
                echo "\n\n__MESSAGE_END__\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                $title = $this->titleGeneratorService->generateTitle($conversation, $request->message, $fullResponse);

                if ($title !== null) {
                    echo "__TITLE_START__\n";
                    echo json_encode(['title' => $title, 'conversation_id' => $conversation->id]);
                    echo "\n__TITLE_END__\n";
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
            }
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

    /**
     * Préparation du contexte IA : instructions utilisateur + historique messages
     */
    private function prepareMessagesForAI($conversation, $user, $processedMessage, $originalMessage)
    {
        // Ajout des instructions personnalisées de l'utilisateur
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

        // Récupération et formatage de l'historique des messages
        $userMessages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) use ($processedMessage, $originalMessage) {
                // Remplacement du message original par la version traitée (commandes)
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
