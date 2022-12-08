<?php

namespace Neosolva\Component\Api\Request;

use Generator;
use Neosolva\Component\Api\Client;
use Neosolva\Component\Api\Exception\ClientException;

class BatchRequest
{
    private array $stack = [];

    public function __construct(private Client $client, private array $options = [])
    {
    }

    public function get(string $path, array $options = []): self
    {
        $this->stack[] = [
            'method' => 'get',
            'path' => $path,
            'options' => $options,
        ];

        return $this;
    }

    public function getItem(string $path, int|string $id, array $options = []): self
    {
        $this->stack[] = [
            'method' => 'getItem',
            'path' => $path,
            'identifier' => $id,
            'options' => $options,
        ];

        return $this;
    }

    public function post(string $path, array $data = [], array $options = []): self
    {
        $this->stack[] = [
            'method' => 'post',
            'path' => $path,
            'data' => $data,
            'options' => $options,
        ];

        return $this;
    }

    public function put(string $path, array $data = [], array $options = []): self
    {
        $this->stack[] = [
            'method' => 'put',
            'path' => $path,
            'data' => $data,
            'options' => $options,
        ];

        return $this;
    }

    public function patch(string $path, array $data = [], array $options = []): self
    {
        $this->stack[] = [
            'method' => 'patch',
            'path' => $path,
            'data' => $data,
            'options' => $options,
        ];

        return $this;
    }

    public function delete(string $path, array $options = []): self
    {
        $this->stack[] = [
            'method' => 'delete',
            'path' => $path,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * @return Generator|array[]
     */
    public function execute(): Generator
    {
        $batchSize = 0;
        $parts = array_chunk($this->stack, $this->getMaxConcurrentRequests(), true);

        foreach ($parts as $part) {
            $responses = [];

            foreach ($part as $key => $data) {
                $responses[] = match ($data['method']) {
                    'get' => $this->client->get($data['path'], $data['options'])->getResponse(),
                    'getItem' => $this->client->getItem($data['path'], $data['identifier'], $data['options'])->getResponse(),
                    'post' => $this->client->post($data['path'], $data['data'], $data['options'])->getResponse(),
                    'put' => $this->client->put($data['path'], $data['data'], $data['options'])->getResponse(),
                    'patch' => $this->client->patch($data['path'], $data['data'], $data['options'])->getResponse(),
                    'delete' => $this->client->delete($data['path'], $data['options'])->getResponse(),
                    default => throw new ClientException('HTTP method not valid.')
                };

                unset($this->stack[$key]);
                ++$batchSize;
            }

            foreach ($responses as $response) {
                yield $this->client->getResult($response);
            }
        }

        return $this;
    }

    /**
     * @return int<1, max>
     */
    public function getMaxConcurrentRequests(): int
    {
        return $this->options['max_concurrent_requests'] ?? Client::MAX_CONCURRENT_REQUESTS;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
