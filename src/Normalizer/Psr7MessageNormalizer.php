<?php

namespace Cyve\HttpMessageSerializer\Normalizer;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class Psr7MessageNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ){
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException(sprintf('%s(): Argument #1 ($data) must be of type array, %s given', __METHOD__, get_debug_type($data)));
        }

        if (is_subclass_of($type, ResponseInterface::class)) {
            [$version, $statusCode, $reason] = explode(' ', $data['start-line']);
            $message = $this->responseFactory
                ->createResponse((int) $statusCode, $reason)
                ->withProtocolVersion(str_replace('HTTP/', '', $version))
                ;
        } else {
            [$method, $target, $version] = explode(' ', $data['start-line']);
            $authority = preg_replace('/^Host: ?/i', '', \array_shift($data['header-fields']));

            $message = $this->requestFactory
                ->createRequest($method, \sprintf('http://%s%s', $authority, $target))
                ->withProtocolVersion(str_replace('HTTP/', '', $version))
            ;
        }

        foreach ($data['header-fields'] as $headerField) {
            preg_match('/([^:]+): ?(.*)/', $headerField, $matches);
            $message = $message->withAddedHeader($matches[1], $matches[2]);
        }

        if (array_key_exists('message-body', $data)) {
            $stream = $this->streamFactory->createStream($data['message-body']);
            $message = $message->withBody($stream);
        }

        return $message;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        return is_subclass_of($type, MessageInterface::class);
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        if (!$object instanceof MessageInterface) {
            throw new \InvalidArgumentException(sprintf('%s(): Argument #1 ($data) must be of type %s, %s given', __METHOD__, MessageInterface::class, get_debug_type($object)));
        }

        $normalizedData = [];

        if ($object instanceof RequestInterface) {
            $normalizedData['start-line'] = implode(' ', [
                $object->getMethod(),
                $object->getRequestTarget(),
                sprintf('HTTP/%s', $object->getProtocolVersion()),
            ]);
        } elseif ($object instanceof ResponseInterface) {
            $normalizedData['start-line'] = implode(' ', [
                sprintf('HTTP/%s', $object->getProtocolVersion()),
                $object->getStatusCode(),
                $object->getReasonPhrase(),
            ]);
        }

        $normalizedData['header-fields'] = [];
        foreach ($object->getHeaders() as $name => $values) {
            if ($name === 'Set-Cookie') {
                foreach ($values as $value) {
                    $normalizedData['header-fields'][] = sprintf('%s: %s', $name, $value);
                }
                break;
            }

            $normalizedData['header-fields'][] = sprintf('%s: %s', $name, implode(',', $values));
        }

        if ($messageBody = $object->getBody()->getContents()) {
            $normalizedData['message-body'] = $messageBody;
        }

        return $normalizedData;
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return $data instanceof MessageInterface;
    }
}
