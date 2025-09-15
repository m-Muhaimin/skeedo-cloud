<?php

declare(strict_types=1);

namespace Plugin\Infrastructure\Repositories\InMemory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Iterator;
use Override;
use Plugin\Domain\Exceptions\PluginNotFoundException;
use Plugin\Domain\PluginWrapper;
use Plugin\Domain\Repositories\PluginRepositoryInterface;
use Plugin\Domain\ValueObjects\Name;
use Plugin\Domain\ValueObjects\Status;
use Plugin\Domain\ValueObjects\Type;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\MaxResults;

class PluginRepository implements PluginRepositoryInterface
{
    private Collection $collection;

    public function __construct()
    {
        $this->collection = new ArrayCollection();
    }

    #[Override]
    public function add(PluginWrapper $wrapper): void
    {
        $this->collection->set(
            $wrapper->context->name->value,
            $wrapper
        );
    }

    #[Override]
    public function remove(PluginWrapper $wrapper): void
    {
        $this->collection->remove($wrapper->context->name->value);
    }

    #[Override]
    public function filterByStatus(Status $status): PluginRepositoryInterface
    {
        return $this->filter(static function (self $repo) use ($status) {
            $repo->collection = $repo->collection->filter(
                static function (PluginWrapper $wrapper) use ($status) {
                    return $wrapper->context->getStatus() == $status;
                }
            );
        });
    }

    #[Override]
    public function filterByType(Type $type): PluginRepositoryInterface
    {
        return $this->filter(static function (self $repo) use ($type) {
            $repo->collection = $repo->collection->filter(
                static function (PluginWrapper $wrapper) use ($type) {
                    return $wrapper->context->type == $type;
                }
            );
        });
    }

    #[Override]
    public function ofName(Name $name): PluginWrapper
    {
        if ($this->collection->containsKey($name->value)) {
            return $this->collection->get($name->value);
        }

        throw new PluginNotFoundException($name->value);
    }

    #[Override]
    public function search(string $terms): PluginRepositoryInterface
    {
        return $this->filter(static function (self $repo) use ($terms) {
            $repo->collection = $repo->collection->filter(
                static function (PluginWrapper $wrapper) use ($terms) {
                    $context = $wrapper->context;

                    return
                        str_contains($context->name->value, $terms)
                        || str_contains($context->tagline->value ?: '', $terms)
                        || str_contains($context->description->value ?: '', $terms)
                        || str_contains($context->title->value ?: '', $terms);
                }
            );
        });
    }

    #[Override]
    public function getIterator(): Iterator
    {
        return $this->collection->getIterator();
    }

    public function slice(int $start, int $size = 20): RepositoryInterface
    {
        return $this->filter(static function (self $repo) use ($start, $size) {
            $repo->collection = new ArrayCollection($repo->collection->slice($start, $size));
        });
    }

    #[Override]
    public function count(): int
    {
        return $this->collection->count();
    }

    #[Override]
    public function flush(): void
    {
        // This method clears all the plugins stored in the repository.
        // It's like pressing the reset button for the plugins!

        // Just kidding, there's nothing to flush here!
        // It's like trying to flush a toilet without any water.
        // But hey, at least we can have a laugh about it!
    }

    protected function filter(callable $filter): PluginRepositoryInterface
    {
        $cloned = clone $this;
        $filter($cloned);

        return $cloned;
    }

    public function setMaxResults(MaxResults $maxResults): static
    {
        return $this->filter(static function (self $repo) use ($maxResults) {
            $repo->collection = new ArrayCollection($repo->collection->slice(0, $maxResults->value));
        });
    }
}
