# cyve/http-message-serializer

Provides an encoder and a normalizer compatible with symfony/serializer to serialize PSR-7 HTTP message in text format.

## Installation
```bash
composer require cyve/http-message-serializer nyholm/psr7 # or any PSR-7 implementation
```

## Usage
```php
use Cyve\HttpMessageSerializer\Encoder\HttpEncoder;
use Cyve\HttpMessageSerializer\Normalizer\Psr7MessageNormalizer;
use Symfony\Component\Serializer\Serializer;

$serializer = new Serializer(
    [new Psr7MessageNormalizer()],
    [new HttpEncoder()],
);
```

## Serializing a message
```php
use Nyholm\Psr7\Request;

$request = new Request(
    method: 'POST',
    uri: 'https://cyve.fr/lorem/ipsum',
    headers: ['Content-Type' => 'application/json'],
    body: '{"foo":"bar"}',
);

$httpContent = $serializer->serialize($request, 'http');

/**
POST /lorem/ipsum HTTP/1.1
Host: cyve.fr
Content-Type: application/json

{"foo":"bar"}
*/
```

## Deserializing a message
```php
use Nyholm\Psr7\Response;

$data = <<<EOF
HTTP/1.1 200 OK
Content-Type: application/json

{"foo":"bar"}
EOF;

$response = $serializer->deserialize($data, Response:class, 'http');
```

## TODO
- Uploaded files in request (https://www.php-fig.org/psr/psr-7/#16-uploaded-files)
- Form input in request
- Multipart messages
- Binary response
- Streaming response ?
- HarEncoder (http://www.softwareishard.com/blog/har-12-spec)
- Normalizer for symfony/http-foundation messages
- Sub requests (Early hints and other 1xx responses)

## Read more
- [HTTP on Mozilla Developer Network](https://developer.mozilla.org/fr/docs/Web/HTTP)
- [HTTP 1.1](https://datatracker.ietf.org/doc/html/rfc2068)
- [HTTP 1.1 : Message Syntax and Routing](https://datatracker.ietf.org/doc/html/rfc7230)
- [HTTP 1.1 : TLS](https://datatracker.ietf.org/doc/html/rfc2817)
- [HTTP 2.0](https://datatracker.ietf.org/doc/html/rfc7540)