<?php

namespace Neosolva\Component\Api;

use Psr\Http\Message\ResponseInterface;

class Client extends \GuzzleHttp\Client
{
    /**
     * Shortcut method to create the client with required parameters.
     *
     * @static
     */
    public static function create(string $url, string $username, string $apiKey, array $options = []): self
    {
        return new self(array_merge($options, [
            'base_uri' => $url,
            'timeout' => 0,
            'allow_redirects' => false,
            'auth' => [$username, $apiKey],
        ]));
    }

    /**
     * @return array|bool|float|int|object|string|null
     */
    public function decode(ResponseInterface $response)
    {
        $body = $response->getBody()->getContents();

        return \GuzzleHttp\json_decode($body);
    }

    public function getBaseUri(): string
    {
        return $this->getConfig('base_uri');
    }
}
