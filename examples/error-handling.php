<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Yusr\Http\YusrClient;
use Yusr\Http\Exceptions\RequestException;
use Yusr\Http\Exceptions\RateLimitException;
use Yusr\Http\Exceptions\NetworkException;
use Yusr\Http\Exceptions\TimeoutException;

$client = YusrClient::getInstance();

try {
    // This might throw various exceptions
    $response = $client->get('https://api.example.com/potentially-failing-endpoint');
} catch (RateLimitException $e) {
    echo "Rate limit exceeded. Limit: {$e->getLimit()}, Time frame: {$e->getTimeFrame()} seconds\n";
    echo "Try again later\n";
} catch (TimeoutException $e) {
    echo "Request timed out after {$e->getTimeout()} seconds\n";
} catch (NetworkException $e) {
    echo "Network error: {$e->getMessage()}\n";
} catch (RequestException $e) {
    echo "Request failed: {$e->getMessage()}\n";
    if ($e->hasResponse()) {
        echo "Status code: " . $e->getResponse()->getStatusCode() . "\n";
    }
} 
