<?php

namespace Cyve\HttpMessageSerializer\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class HttpEncoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'http';

    public function decode(string $data, string $format, array $context = [])
    {
        $lines = explode(PHP_EOL, $data);

        // start line
        $output = ['start-line' => current($lines), 'header-fields' => []];

        // the first empty line is the end of the header section
        while(!empty($line = next($lines))) {
            $output['header-fields'][] = $line;
        }

        // the rest of the file is the body
        $body = [];
        while(false !== $line = next($lines)) {
            $body[] = $line;
        }
        $output['message-body'] = implode(PHP_EOL, $body);

        return $output;
    }

    public function supportsDecoding(string $format)
    {
        return self::FORMAT === $format;
    }

    public function encode(mixed $data, string $format, array $context = []): string
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException(sprintf('%s(): Argument #1 ($data) must be of type array, %s given', __METHOD__, get_debug_type($data)));
        }

        if (empty($data['start-line'])) {
            throw new \OutOfRangeException('`start-line` property should not be empty');
        }

        $output = [$data['start-line']];

        foreach ($data['header-fields'] ?? [] as $headerField) {
            $output[] = $headerField;
        }
        $output[] = '';

        if (array_key_exists('message-body', $data)) {
            $output[] = $data['message-body'];
        }

        return implode(PHP_EOL, $output);
    }

    public function supportsEncoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}
