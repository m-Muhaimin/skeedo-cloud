<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services;

use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Shared\Infrastructure\Services\ModelRegistry;
use Override;
use Traversable;

abstract class AbstractBaseService implements AiServiceInterface
{
    protected ?array $models = null;

    public function __construct(
        private ModelRegistry $registry,
        private string $service,
        private string $type
    ) {}

    #[Override]
    public function supportsModel(Model $model): bool
    {
        $this->parseDirectory();
        return array_key_exists($model->value, $this->models);
    }

    #[Override]
    public function getSupportedModels(): Traversable
    {
        $this->parseDirectory();

        foreach ($this->models as $key => $model) {
            yield new Model($key);
        }
    }

    /**
     * Parses and caches the available models from the registry.
     * 
     * This method:
     * 1. Checks if models are already cached (returns early if they are)
     * 2. Filters the registry directory for the specified service
     * 3. Extracts and caches models of the specified type
     * 
     * The cached models are stored in the $models property as an associative 
     * array where the keys are model identifiers and the values are the model 
     * configurations.
     * 
     * @return void
     */
    private function parseDirectory(): void
    {
        if ($this->models !== null) {
            return;
        }

        $services = array_filter(
            $this->registry['directory'],
            fn($service) => $service['key'] === $this->service
        );

        if (count($services) === 0) {
            $this->models = [];
            return;
        }

        $models = [];
        foreach ($services as $service) {
            $models = array_merge(
                $models,
                array_filter(
                    $service['models'],
                    fn($model) =>
                    $model['type'] === $this->type
                        && ($model['enabled'] ?? false)
                )
            );
        }

        $this->models = array_reduce($models, function ($carry, $model) {
            $carry[$model['key']] = $model;
            return $carry;
        }, []);
    }
}
