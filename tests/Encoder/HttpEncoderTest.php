<?php

namespace Cyve\HttpMessageSerializer\Tests\Encoder;

use Cyve\HttpMessageSerializer\Encoder\HttpEncoder;
use PHPUnit\Framework\TestCase;

class HttpEncoderTest extends TestCase
{
    public function testSupportEncoding()
    {
        $this->assertTrue((new HttpEncoder())->supportsEncoding('http'));
    }

    public function testEncodeHttpMessage()
    {
        $encoder = new HttpEncoder();
        $result = $encoder->encode([
            'start-line' => 'HTTP/1.1 200 Ok',
            'header-fields' => [
                'Date: Thu, 01 Jan 1970 00:00:00 GMT',
                'Content-Type: text/html',
            ],
            'message-body' => <<<EOF
                <h1>Hello world</h1>

                <p>Lorem ipsum sit dolor amet</p>
                EOF
,
        ], 'http');

        $expected = <<<EOL
            HTTP/1.1 200 Ok
            Date: Thu, 01 Jan 1970 00:00:00 GMT
            Content-Type: text/html
            
            <h1>Hello world</h1>

            <p>Lorem ipsum sit dolor amet</p>
            EOL;

        $this->assertEquals($expected, $result);
    }

    public function testEncodeHttpMessageWithoutBody()
    {
        $encoder = new HttpEncoder();
        $result = $encoder->encode([
            'start-line' => 'GET /lorem/ipsum?foo=bar HTTP/1.1',
            'header-fields' => [
                'Host: cyve.fr',
                'Accept:text/html,*.*',
            ],
        ], 'http');

        $expected = <<<EOL
            GET /lorem/ipsum?foo=bar HTTP/1.1
            Host: cyve.fr
            Accept:text/html,*.*
            
            EOL;

        $this->assertEquals($expected, $result);
    }

    public function testEncodeHttpMessageWithoutStartLineShouldThrowException()
    {
        $this->expectException(\OutOfRangeException::class);

        $encoder = new HttpEncoder();
        $encoder->encode([], 'http');
    }

    public function testSupportDecoding()
    {
        $this->assertTrue((new HttpEncoder())->supportsDecoding('http'));
    }

    public function testDecodeHttpMessage()
    {
        $input = <<<EOL
            HTTP/1.1 200 OK
            Date: Thu, 01 Jan 1970 00:00:00 GMT
            Content-Type: text/html
            
            <h1>Hello world</h1>
            
            <p>Lorem ipsum sit dolor amet</p>
            EOL;

        $encoder = new HttpEncoder();
        $result = $encoder->decode($input, 'http');

        $expected = [
            'start-line' => 'HTTP/1.1 200 OK',
            'header-fields' => [
                'Date: Thu, 01 Jan 1970 00:00:00 GMT',
                'Content-Type: text/html',
            ],
            'message-body' => <<<EOL
                <h1>Hello world</h1>

                <p>Lorem ipsum sit dolor amet</p>
                EOL
        ];

        $this->assertEquals($expected, $result);
    }

    public function testDecodeHttpMessageWithoutBody()
    {
        $input = <<<EOL
            GET /lorem/ipsum?foo=bar HTTP/1.1
            Host: cyve.fr
            Accept: text/html,*/*

            EOL;

        $encoder = new HttpEncoder();
        $result = $encoder->decode($input, 'http');

        $expected = [
            'start-line' => 'GET /lorem/ipsum?foo=bar HTTP/1.1',
            'header-fields' => [
                'Host: cyve.fr',
                'Accept: text/html,*/*',
            ],
            'message-body' => ''
        ];

        $this->assertEquals($expected, $result);
    }
}
