
# 🚀 YusrClient - A Powerful PHP HTTP Client

<p align="center">
  <img src="art/logo.png" alt="YusrClient Logo" width="200">
</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tal7aouy/yusr.svg?style=flat-square)](https://packagist.org/packages/tal7aouy/yusr)
[![Total Downloads](https://img.shields.io/packagist/dt/tal7aouy/yusr.svg?style=flat-square)](https://packagist.org/packages/tal7aouy/yusr)
[![License](https://img.shields.io/packagist/l/tal7aouy/yusr.svg?style=flat-square)](https://packagist.org/packages/tal7aouy/yusr)

YusrClient is a robust and easy-to-use PHP HTTP client that simplifies making HTTP requests in your PHP applications. Built with modern PHP practices, it implements the PSR-18 HTTP Client interface and provides a fluent API for sending HTTP requests.

## ✨ Key Features

- 🔒 PSR-18 HTTP Client compliant
- 🛠 Singleton pattern implementation
- 🚦 Full HTTP method support (GET, POST, PUT, DELETE, PATCH)
- 🔄 Automatic retry mechanism with exponential backoff
- 🔧 Highly customizable request options
- 🧩 Intuitive fluent interface
- ⏱ Configurable timeouts
- 🔐 SSL verification support

## 📦 Installation

```bash
composer require tal7aouy/yusr
```

## 🚀 Quick Start

```php
use Yusr\Http\YusrClient;

// Get client instance
$client = YusrClient::getInstance();

// Make a GET request
$response = $client->get('https://api.example.com/users');

// Work with response
$statusCode = $response->getStatusCode();
$data = $response->getBody()->getContents();
$headers = $response->getHeaders();
```

## ⚙️ Configuration

```php
$client = YusrClient::getInstance([
    'timeout' => 30,
    'allow_redirects' => true,
    'verify' => true,
    'retry' => [
        'max_attempts' => 3,
        'delay' => 1000 // milliseconds
    ]
]);
```

## 📘 Available Methods

### HTTP Methods
```php
$client->get(string $uri, array $options = []);
$client->post(string $uri, array $options = []);
$client->put(string $uri, array $options = []);
$client->delete(string $uri, array $options = []);
$client->patch(string $uri, array $options = []);
```

### Request Options
- `query` - Array of URL query parameters
- `headers` - Custom request headers
- `body` - Request body (for POST, PUT, PATCH)
- `timeout` - Request timeout in seconds
- `allow_redirects` - Follow redirects (boolean)
- `verify` - SSL certificate verification
- `retry` - Retry configuration for failed requests

## 🔄 Retry Mechanism

YusrClient includes a sophisticated retry mechanism with exponential backoff:

```php
$client = YusrClient::getInstance([
    'retry' => [
        'max_attempts' => 3,
        'delay' => 1000,
        'multiplier' => 2
    ]
]);
```

## 🤝 Contributing

Contributions are always welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.

## 📝 License

This project is licensed under the [MIT License](LICENSE).

## 🙏 Support

If you find this package helpful, please consider giving it a star ⭐️