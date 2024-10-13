# 🚀 YusrClient - A Powerful PHP HTTP Client

<p align="center">
  <img src="art/logo.png" alt="YusrClient Logo" width="300">
</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tal7aouy/yusr.svg?style=flat-square)](https://packagist.org/packages/tal7aouy/yusr)
[![Total Downloads](https://img.shields.io/packagist/dt/tal7aouy/yusr.svg?style=flat-square)](https://packagist.org/packages/tal7aouy/yusr)
[![License](https://img.shields.io/packagist/l/tal7aouy/yusr.svg?style=flat-square)](https://packagist.org/packages/tal7aouy/yusr)

YusrClient is a robust and easy-to-use PHP HTTP client that simplifies making HTTP requests in your PHP applications. It implements the PSR-18 HTTP Client interface and provides a fluent API for sending HTTP requests.

## 🌟 Features

- 🔒 Implements PSR-18 HTTP Client interface
- 🛠 Singleton pattern for easy global access
- 🚦 Supports all major HTTP methods (GET, POST, PUT, DELETE, PATCH)
- 🔧 Customizable options for each request
- 🧩 Easy-to-use fluent interface
- 🔁 Automatic handling of redirects
- ⏱ Configurable timeout
- 🔐 SSL verification

## 📦 Installation

You can install the package via composer:

```bash
composer require tal7aouy/yusr
```

## 🚀 Usage

Here's a quick example of how to use YusrClient:

```php
use Yusr\Http\YusrClient;

// Get the YusrClient instance
$client = YusrClient::getInstance();

// Make a GET request
$response = $client->get('https://jsonplaceholder.typicode.com/posts');

// Access response data
$statusCode = $response->getStatusCode();
$body = $response->getBody()->getContents();
$headers = $response->getHeaders();
echo $body. PHP_EOL;
```

## 🛠 Configuration

You can configure the default options when getting the YusrClient instance:

```php
$client = YusrClient::getInstance([
    'timeout' => 60,
    'allow_redirects' => false,
    'verify' => false,
]);
```

## 📘 API Reference

### Available Methods

- `get(string $uri, array $options = []): ResponseInterface`
- `post(string $uri, array $options = []): ResponseInterface`
- `put(string $uri, array $options = []): ResponseInterface`
- `delete(string $uri, array $options = []): ResponseInterface`
- `patch(string $uri, array $options = []): ResponseInterface`
- `request(string $method, string $uri, array $options = []): ResponseInterface`

### Options

- `query`: array of query parameters to add to the URI
- `headers`: array of headers to send with the request
- `body`: the body of the request (for POST, PUT, PATCH)
- `timeout`: request timeout in seconds
- `allow_redirects`: whether to follow redirects
- `verify`: whether to verify SSL certificates

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
