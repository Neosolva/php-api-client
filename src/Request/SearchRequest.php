<?php

namespace Neosolva\Component\Api\Request;

use Generator;
use InvalidArgumentException;
use Neosolva\Component\Api\Client;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SearchRequest implements \IteratorAggregate
{
    private static ?PropertyAccessorInterface $propertyAccessor = null;

    public function __construct(private Client $client,
                                private string $path,
                                private array $filters,
                                private ?int $page = 1,
                                private array $options = [])
    {
    }

    public static function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (!self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return self::$propertyAccessor;
    }

    /**
     * @return Generator|array[]
     */
    public function getIterator(): Generator
    {
        yield from $this->iterate();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = [];

        foreach ($filters as $name => $value) {
            $this->setFilter($name, $value);
        }

        return $this;
    }

    public function setFilter(string $name, mixed $value): self
    {
        self::getPropertyAccessor()->setValue($this->filters, $name, $value);

        return $this;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(?int $page = 1): self
    {
        if ($page < 1) {
            throw new InvalidArgumentException('The argument #0 $age must be an integer greater than 0.');
        }

        $this->page = $page;

        return $this;
    }

    public function setFirstPage(): self
    {
        $this->page = 1;

        return $this;
    }

    public function setNextPage(): self
    {
        ++$this->page;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return Generator|array[]
     */
    public function iterate(array $options = []): Generator
    {
        $batchRequest = $this->client->createBatch();
        $nbPages = $this->getNbPages();
        $nextPage = $this->page;

        $options = $this->generateOptions($options);

        while ($nextPage <= $nbPages) {
            $options['query']['page'] = $nextPage;
            $batchRequest->get($this->path, $options);
            ++$nextPage;
        }

        foreach ($batchRequest->execute() as $records) {
            foreach ($this->getResultFromArray($records) as $record) {
                yield $record;
            }
        }
    }

    public function getNbItems(): int
    {
        $result = $this->getResult();

        return $result['hydra:totalItems'] ?? 0;
    }

    public function getNbPages(): int
    {
        $result = $this->getResult();
        $lastPageUri = $result['hydra:view']['hydra:last'] ?? null;

        if (!$lastPageUri || !preg_match('#(\?|\&)page\=(\d+)#', $lastPageUri, $matches)) {
            return 1;
        }

        return (int) $matches[2];
    }

    public function getResultFromArray(array $result): array
    {
        return $result['hydra:member'] ?? [];
    }

    public function getResult(array $options = []): array
    {
        return $this->client->getResult($this->execute($options));
    }

    public function execute(array $options = []): ResponseInterface
    {
        return $this->client->get($this->path, $this->generateOptions($options))->getResponse();
    }

    /**
     * @internal
     */
    private function generateOptions(array $options = []): array
    {
        $options['query'] = array_merge($this->options['query'] ?? [], $this->filters);
        $options['query']['page'] = $this->page;

        return $options;
    }
}
