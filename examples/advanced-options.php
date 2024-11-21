<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Yusr\Http\YusrClient;

$client = YusrClient::getInstance([
  'timeout' => 60,
  'verify' => false, // Disable SSL verification (not recommended for production)
  'headers' => [
    'User-Agent' => 'YusrClient/1.0',
    'Authorization' => 'Bearer your-token-here',
  ],
]);

// GET request with query parameters
$response = $client->get('https://api.example.com/search', [
  'query' => [
    'q' => 'search term',
    'page' => 1,
    'limit' => 10,
  ],
]);

// PUT request with custom headers
$response = $client->put('https://api.example.com/users/123', [
  'headers' => [
    'X-Custom-Header' => 'value',
  ],
  'body' => json_encode([
    'status' => 'active',
  ]),
]);
