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
    public function index(Request $request)
    {
        $models = (new ChatService())->getModels();
        $selectedModel = Auth::user()->preferred_model ?? ChatService::DEFAULT_MODEL;

        $conversations = Conversation::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->get();

        //Récupère l'ID de conversation depuis le flash ou l'URL
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
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'title' => 'Nouvelle conversation',
                'model' => $request->model
            ]);
        }

        // Sauvegarder le modèle préféré de l'utilisateur
        $user->update(['preferred_model' => $request->model]);

        // Traiter les commandes personnalisées
        $processedMessage = $this->processCustomCommands($request->message, $user);

        // Sauvegarder le message utilisateur (original)
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $request->message
        ]);

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
            ->map(function ($message) use ($processedMessage, $request) {
                if ($message->content === $request->message && $message->role === 'user') {
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

        $finalMessages = array_merge($systemMessages, $userMessages);

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

                    usleep(10000); // 10ms
                }
            );

            // Sauvegarder le message final
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $fullResponse
            ]);

            if ($conversation->messages()->count() === 2 && $conversation->title === 'Nouvelle conversation') {
                $this->generateConversationTitle($conversation, $request->message, $fullResponse);
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

        $nextConversation = Conversation::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->first();

        return redirect()->route('ask.index')->with([
            'selectedConversationId' => $nextConversation?->id,
            'shouldFocusInput' => true
        ]);
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
                    'content' => $userMessage
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

    private function processCustomCommands($message, $user)
    {
        if (strpos(trim($message), '/meteo') === 0) {
            $city = trim(substr($message, 6));
            if (empty($city)) {
                return "Veuillez spécifier une ville. Exemple: /meteo Paris";
            }

            $weatherService = new \App\Services\WeatherService();
            $weather = $weatherService->getCurrentWeather($city);

            if (isset($weather['error'])) {
                return "Erreur météo: " . $weather['error'];
            }

            $temp = $weather['main']['temp'] ?? 'N/A';
            $description = $weather['weather'][0]['description'] ?? 'N/A';
            $humidity = $weather['main']['humidity'] ?? 'N/A';

            return "Météo actuelle à {$city}: {$temp}°C, {$description}, humidité {$humidity}%. Présente ces informations de manière naturelle.";
        }

        if (!$user->custom_commands) {
            return $message;
        }

        $commands = $this->parseCustomCommands($user->custom_commands);

        foreach ($commands as $command => $description) {
            if (strpos(trim($message), $command) === 0) {
                $args = trim(substr($message, strlen($command)));
                $processedMessage = $description;
                if ($args) {
                    $processedMessage .= " " . $args;
                }
                return $processedMessage;
            }
        }

        return $message;
    }

    private function parseCustomCommands($customCommands)
    {
        $commands = [];
        $lines = explode("\n", $customCommands);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match('/^(\/\w+)\s*:\s*(.+)$/', $line, $matches)) {
                $command = trim($matches[1]);
                $description = trim($matches[2]);
                $commands[$command] = $description;
            }
        }

        return $commands;
    }
}
