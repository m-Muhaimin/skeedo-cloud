<?php

declare(strict_types=1);

namespace Option\Infrastructure;

use Adbar\Dot;
use ArrayIterator;
use Easy\Container\ResolverInterface;
use Option\Application\Commands\ListOptionsCommand;
use Option\Domain\Entities\OptionEntity;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Throwable;
use Traversable;

class OptionResolver implements ResolverInterface
{
    /** @var Traversable<OptionEntity> */
    private Traversable $options;
    private bool $isRetrieved = false;
    private Dot $map;

    public function __construct(
        private Dispatcher $dispatcher,
        private string $prefix = 'option.'
    ) {
        $this->map = new Dot;
    }

    /**
     * @throws NoHandlerFoundException
     */
    #[Override]
    public function canResolve(string $id): bool
    {
        if (!str_starts_with($id, $this->prefix)) {
            return false;
        }

        $this->retrieveOptions();
        return $this->map->has($id);
    }

    /** @throws NoHandlerFoundException */
    #[Override]
    public function resolve(string $id): mixed
    {
        if (!str_starts_with($id, $this->prefix)) {
            return null;
        }

        $this->retrieveOptions();
        $value = $this->map->get($id, null);

        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        // Check if input can be converted to integer
        if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
            return (int) $value;
        }

        // Check if input can be converted to float
        if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
            return (float) $value;
        }

        // Check if input can be converted to boolean
        if (strtolower($value) === "true" || strtolower($value) === "false") {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        // Return original input if no conversion is possible
        return $value;
    }

    /**
     * @return array<string,string|array>
     * @throws NoHandlerFoundException
     */
    public function getOptionMap(): array
    {
        $this->retrieveOptions();
        return $this->map->all();
    }

    /**
     * @return Traversable<OptionEntity> 
     * @throws NoHandlerFoundException
     */
    public function getOptions(): Traversable
    {
        $this->retrieveOptions();
        return $this->options;
    }

    /**
     * @throws NoHandlerFoundException
     */
    private function retrieveOptions(): void
    {
        if ($this->isRetrieved) {
            return;
        }

        try {
            $cmd = new ListOptionsCommand();
            $this->options = $this->dispatcher->dispatch($cmd);

            foreach ($this->options as $option) {
                $value = json_decode($option->getValue()->value, true);

                $this->map->set(
                    $this->prefix . $option->getKey()->value,
                    is_array($value) ? $value : $option->getValue()->value
                );
            }
        } catch (Throwable $th) {
            // Option repository is not resolvable. Probably Database is not 
            // configured yet.
            $this->isRetrieved = true;
            $this->options = new ArrayIterator([]);
            return;
        }


        $this->isRetrieved = true;
    }
}
