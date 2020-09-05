<?php

namespace Neosolva\Component\Api;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Serializer;

class Client extends \GuzzleHttp\Client
{
    private $serializer;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->serializer = new Serializer([
            new DateTimeNormalizer([
                DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
            ]),
            new DateTimeZoneNormalizer(),
        ], [
            new JsonEncoder(),
        ]);
    }

    public static function create(string $url, string $username, string $apiKey, array $options = []): self
    {
        return new self(array_merge($options, [
            'base_uri' => $url,
            'timeout' => 0,
            'allow_redirects' => false,
            'auth' => [$username, $apiKey],
        ]));
    }

    public function encode(array $data = []): string
    {
        return (string) $this->serializer->encode($data, JsonEncoder::FORMAT);
    }

    public function decode(ResponseInterface $response): array
    {
        $body = $response->getBody()->getContents();

        return $this->serializer->decode($body ?: '', JsonEncoder::FORMAT);
    }

    public function getBaseUri(): string
    {
        return $this->getConfig('base_uri');
    }
}
