<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WeatherService
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.weather.api_key');
    }

    public function getCurrentWeather($city)
    {
        $response = Http::get("https://api.openweathermap.org/data/2.5/weather", [
            'q' => $city,
            'appid' => $this->apiKey,
            'units' => 'metric',
            'lang' => 'fr'
        ]);

        return $response->json();
    }
}
