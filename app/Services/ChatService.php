<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class ChatService
{
    private $baseUrl;
    private $apiKey;
    private $client;
    public const DEFAULT_MODEL = 'openai/gpt-4.1-mini';

    public function __construct()
    {
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->apiKey = config('services.openrouter.api_key');
        $this->client = $this->createOpenAIClient();
    }

    public function getModels(): array
    {
        return cache()->remember('openai.models', now()->addHour(), function () {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/models');

            return collect($response->json()['data'])
                ->sortBy('name')
                ->map(function ($model) {
                    return [
                        'id' => $model['id'],
                        'name' => $model['name'],
                        'context_length' => $model['context_length'],
                        'max_completion_tokens' => $model['top_provider']['max_completion_tokens'],
                        'pricing' => $model['pricing'],
                    ];
                })
                ->values()
                ->all();
        });
    }

    public function sendMessage(array $messages, string $model = null, float $temperature = 0.7): string
    {
        try {
            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
            }

            $messages = [$this->getChatSystemPrompt(), ...$messages];
            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            logger()->error('Erreur dans sendMessage:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function sendMessageStreamed(array $messages, string $model, callable $onChunk): void
    {
        $models = collect($this->getModels());
        if (!$model || !$models->contains('id', $model)) {
            $model = self::DEFAULT_MODEL;
        }

        $messages = [$this->getChatSystemPrompt(), ...$messages];

        $url = $this->baseUrl . '/chat/completions';
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];
        $body = [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
        ];

        $guzzle = new Client();
        $response = $guzzle->post($url, [
            'headers' => $headers,
            'json' => $body,
            'stream' => true,
        ]);

        $stream = $response->getBody();
        $buffer = '';

        while (!$stream->eof()) {
            $chunk = $stream->read(1024);
            $buffer .= $chunk;

            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line === 'data: [DONE]') {
                    continue;
                }
                if (strpos($line, 'data: ') === 0) {
                    $data = substr($line, 6);
                    try {
                        $decoded = json_decode($data, true);
                        if (isset($decoded['choices'][0]['delta']['content'])) {
                            $onChunk($decoded['choices'][0]['delta']['content']);
                        }
                    } catch (\Exception $e) {
                        logger()->error('Erreur de parsing SSE:', ['line' => $line, 'error' => $e->getMessage()]);
                    }
                }
            }
        }
    }

    private function createOpenAIClient(): \OpenAI\Client
    {
        return \OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withBaseUri($this->baseUrl)
            ->make();
    }

    private function getChatSystemPrompt(): array
    {
        $user = auth()->user();
        $now = now()->locale('fr')->format('l d F Y H:i');

        return [
            'role' => 'system',
            'content' => <<<EOT
                Tu es un assistant de chat. La date et l'heure actuelle est le {$now}.
                Tu es actuellement utilisé par {$user->name}.
                EOT,
        ];
    }
}
