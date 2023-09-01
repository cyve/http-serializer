<?php

namespace Cyve\HttpMessageSerializer\Tests\Normalizer;

use Cyve\HttpMessageSerializer\Normalizer\Psr7MessageNormalizer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

class Psr7MessageNormalizerTest extends TestCase
{
    private static Psr17Factory $psr17Factory;
    private static Psr7MessageNormalizer $normalizer;

    public static function setUpBeforeClass(): void
    {
        self::$psr17Factory = new Psr17Factory();
        self::$normalizer = new Psr7MessageNormalizer(self::$psr17Factory, self::$psr17Factory, self::$psr17Factory);
    }

    public function testSupportNormalization()
    {
        $this->assertTrue(self::$normalizer->supportsNormalization(self::$psr17Factory->createRequest('GET', '/')));
        $this->assertTrue(self::$normalizer->supportsNormalization(self::$psr17Factory->createResponse(200)));
    }

    public function testNormalizeRequest()
    {
        $request = new Request(
            method: 'GET',
            uri: 'https://cyve.fr/lorem/ipsum?foo=bar',
            headers: ['Accept' => ['text/html', '*/*']],
        );

        $result = self::$normalizer->normalize($request);

        $expected = [
            'start-line' => 'GET /lorem/ipsum?foo=bar HTTP/1.1',
            'header-fields' => [
                'Host: cyve.fr',
                'Accept: text/html,*/*',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testNormalizeResponse()
    {
        $response = new Response(
            status: 200,
            headers: [
                'Date' => 'Thu, 01 Jan 1970 00:00:00 GMT',
                'Content-Type' => 'text/html',
                'Set-Cookie' => ['foo=bar', 'lorem=ipsum'],
            ],
            body: '<h1>Hello world</h1>',
        );

        $result = self::$normalizer->normalize($response);

        $expected = [
            'start-line' => 'HTTP/1.1 200 OK',
            'header-fields' => [
                'Date: Thu, 01 Jan 1970 00:00:00 GMT',
                'Content-Type: text/html',
                'Set-Cookie: foo=bar',
                'Set-Cookie: lorem=ipsum',
            ],
            'message-body' => '<h1>Hello world</h1>',
        ];

        $this->assertEquals($expected, $result);
    }

    public function testSupportDenormalization()
    {
        $this->assertTrue(self::$normalizer->supportsDenormalization([], Request::class));
        $this->assertTrue(self::$normalizer->supportsDenormalization([], Response::class));
    }

    public function testDenormalizeRequest()
    {
        $input = [
            'start-line' => 'GET /lorem/ipsum?foo=bar HTTP/1.1',
            'header-fields' => [
                'Host: cyve.fr',
                'Accept: text/html,*/*',
            ],
        ];

        $result = self::$normalizer->denormalize($input, Request::class);

        $this->assertInstanceOf(Request::class, $result);
        $this->assertEquals('GET', $result->getMethod());
        $this->assertEquals('http://cyve.fr/lorem/ipsum?foo=bar', $result->getUri());
        $this->assertEquals('/lorem/ipsum?foo=bar', $result->getRequestTarget());
        $this->assertEquals([
            'Host' => ['cyve.fr'],
            'Accept' => ['text/html,*/*'],
        ], $result->getHeaders());
        $this->assertEmpty($result->getBody()->getContents());
    }

    public function testDenormalizeResponse()
    {
        $input = [
            'start-line' => 'HTTP/1.1 200 Ok',
            'header-fields' => [
                'Date: Thu, 01 Jan 1970 00:00:00 GMT',
                'Content-Type: text/html',
                'Set-Cookie: foo=bar',
                'Set-Cookie: lorem=ipsum',
            ],
            'message-body' => '<h1>Hello world</h1>',
        ];

        $result = self::$normalizer->denormalize($input, Response::class);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('Ok', $result->getReasonPhrase());
        $this->assertEquals([
            'Date' => ['Thu, 01 Jan 1970 00:00:00 GMT'],
            'Content-Type' => ['text/html'],
            'Set-Cookie' => [
                'foo=bar',
                'lorem=ipsum',
            ],
        ], $result->getHeaders());
        $this->assertEquals('<h1>Hello world</h1>', $result->getBody()->getContents());
    }
}
