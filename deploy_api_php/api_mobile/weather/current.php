<?php
require_once '../config.php';

requireAuth();

$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;

if (!$lat || !$lon) {
    jsonError('Latitude and longitude are required');
}

$apiKey = 'VOTRE_CLE_API_OPENWEATHER';

$url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric&lang=fr";

$response = file_get_contents($url);

if ($response === false) {
    jsonError('Failed to fetch weather data', 500);
}

$data = json_decode($response, true);

if (!isset($data['main'])) {
    jsonError('Invalid weather data', 500);
}

$weather = [
    'temperature' => round($data['main']['temp']),
    'conditions' => $data['weather'][0]['description'] ?? 'N/A',
    'icon' => $data['weather'][0]['icon'] ?? '',
    'humidity' => $data['main']['humidity'] ?? 0,
    'wind_speed' => round($data['wind']['speed'] ?? 0)
];

jsonResponse($weather);
