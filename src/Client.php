<?php

namespace Neosolva\Component\Api;

use DateTime;
use Neosolva\Component\Api\Exception\ClientException;
use Neosolva\Component\Api\Exception\RequestException;
use Neosolva\Component\Api\Request\BatchRequest;
use Neosolva\Component\Api\Request\SearchRequest;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Client
{
    public const MAX_CONCURRENT_REQUESTS = 50;

    private ?string $token = null;
    private ?DateTime $tokenExpiresAt = null;
    private ?ResponseInterface $response = null;
    private CacheInterface $cache;

    public function __construct(private HttpClientInterface $httpClient,
                                private string $username,
                                private string $password,
                                private array $defaultOptions = [],
                                ?CacheInterface $cache = null)
    {
        $this->cache = $cache ?: new ArrayAdapter();
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
        $tokenCacheKey = sprintf('%s.%d', str_replace('\\', '_', self::class), spl_object_id($this));
        $token = $this->cache->get($tokenCacheKey, function (ItemInterface $item) {
            $item->expiresAfter(60*50);
            $this->response = null;

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

            return $this->getResult($this->response)['token'];
        });

        $options['headers']['Authorization'] = sprintf('Bearer %s', $token);
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

    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        if ($username !== $this->username) {
            $this->clearToken();
        }

        $this->username = $username;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        if ($password !== $this->password) {
            $this->clearToken();
        }

        $this->password = $password;

        return $this;
    }

    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
    }

    public function setDefaultOptions(array $defaultOptions): self
    {
        $this->defaultOptions = $defaultOptions;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getTokenExpiresAt(): ?DateTime
    {
        return $this->tokenExpiresAt;
    }

    public function clearToken(): self
    {
        $this->token = null;
        $this->tokenExpiresAt = null;

        return $this;
    }
}
