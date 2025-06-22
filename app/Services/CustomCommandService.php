<?php

namespace App\Services;

use App\Models\User;

class CustomCommandService
{
    public function processCommands(string $message, User $user): string
    {
        // Commande météo
        if (strpos(trim($message), '/meteo') === 0) {
            return $this->processWeatherCommand($message);
        }

        // Commandes personnalisées
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

    private function processWeatherCommand(string $message): string
    {
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

    private function parseCustomCommands(string $customCommands): array
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
