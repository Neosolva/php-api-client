<?php

namespace Neosolva\Component\Api;

use DateTime;
use Neosolva\Component\Api\Exception\ClientException;
use Neosolva\Component\Api\Exception\RequestException;
use Neosolva\Component\Api\Request\BatchRequest;
use Neosolva\Component\Api\Request\SearchRequest;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Client
{
    public const MAX_CONCURRENT_REQUESTS = 50;

    private ?string $token = null;
    private ?DateTime $tokenExpiresAt = null;
    private ?ResponseInterface $response = null;

    public function __construct(private HttpClientInterface $httpClient,
                                private string $username,
                                private string $password,
                                private array $defaultOptions = [])
    {
    }

    public static function create(string $apiUrl, string $username, string $password, array $defaultOptions = []): self
    {
        return new self(HttpClient::create([
            'base_uri' => $apiUrl,
            'verify_peer' => false,
            'verify_host' => false,
        ]), $username, $password, $defaultOptions);
    }

    public function get(string $path, array $options = []): self
    {
        $this->request('GET', $path, $options);

        return $this;
    }

    public function getItem(string $path, int|string $id, array $options = []): self
    {
        $path = sprintf('%s/%s', $path, $id);
        $this->request('GET', $path, $options);

        return $this;
    }

    public function post(string $path, array $data = [], array $options = []): self
    {
        $this->request('POST', $path, array_merge($options, [
            'json' => $data,
        ]));

        return $this;
    }

    public function put(string $path, array $data = [], array $options = []): self
    {
        $this->request('PUT', $path, array_merge($options, [
            'json' => $data,
        ]));

        return $this;
    }

    public function patch(string $path, array $data = [], array $options = []): self
    {
        $this->request('PATCH', $path, array_merge($options, [
            'json' => $data,
        ]));

        return $this;
    }

    public function delete(string $path, array $options = []): self
    {
        $this->request('DELETE', $path, $options);

        return $this;
    }

    public function search(string $path, array $filters = [], array $options = []): SearchRequest
    {
        return new SearchRequest($this, $path, $filters, 1, $options);
    }

    public function batch(): BatchRequest
    {
        return new BatchRequest($this);
    }

    public function request(string $method, string $path, array $options = []): ResponseInterface
    {
        if ($this->tokenExpiresAt && $this->tokenExpiresAt <= new DateTime()) {
            $this->token = null;
            $this->tokenExpiresAt = null;
        }

        $this->response = null;

        if (!$this->token) {
            try {
                $this->response = $this->httpClient->request('POST', '/authenticate', [
                    'json' => [
                        'username' => $this->username,
                        'password' => $this->password,
                    ],
                ]);
            } catch (TransportExceptionInterface $exception) {
                throw new RequestException($exception->getMessage(), 0, $exception);
            }

            $this->token = $this->getResult($this->response)['token'];
            $this->tokenExpiresAt = new DateTime('+45 minutes');
        }

        $options['headers']['Authorization'] = sprintf('Bearer %s', $this->token);
        $this->response = null;

        try {
            return $this->response = $this->httpClient->request($method, $path, array_merge($this->defaultOptions, $options));
        } catch (TransportExceptionInterface $exception) {
            throw new RequestException($exception->getMessage(), 0, $exception);
        }
    }

    public function getResult(?ResponseInterface $response = null): array
    {
        $response = $response ?: $this->getResponse();

        try {
            return $response->toArray();
        } catch (\Throwable $exception) {
            throw new RequestException($exception->getMessage(), 0, $exception);
        }
    }

    public function getResponse(): ResponseInterface
    {
        if (!$this->response) {
            throw new ClientException('No response found - Please make a request first.');
        }

        return $this->response;
    }
}
