<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Yusr\Http\YusrClient;

$client = YusrClient::getInstance();

// Basic GET request
$response = $client->get('https://api.example.com/users');
echo "GET Response Status: " . $response->getStatusCode() . "\n";
echo "Response Body: " . $response->getBody() . "\n";

// POST request with JSON body
$response = $client->post('https://api.example.com/users', [
  'headers' => [
    'Content-Type' => 'application/json',
  ],
  'body' => json_encode([
    'name' => 'John Doe',
    'email' => 'john@example.com',
  ]),
]);
