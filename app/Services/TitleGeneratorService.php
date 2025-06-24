<?php

namespace App\Services;

use App\Models\Conversation;

class TitleGeneratorService
{
    public function generateTitle(Conversation $conversation, string $userMessage, string $aiResponse): ?string
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

            $cleanTitle = trim($title);

            // Mettre à jour la conversation avec le nouveau titre
            $conversation->update(['title' => $cleanTitle]);

            // Retourner le titre pour le streaming
            return $cleanTitle;
        } catch (\Exception $e) {
            // En cas d'erreur, ne pas changer le titre
            return null;
        }
    }
}
