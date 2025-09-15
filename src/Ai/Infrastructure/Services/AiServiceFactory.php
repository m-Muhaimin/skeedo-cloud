<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services;

use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Override;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Traversable;

class AiServiceFactory implements AiServiceFactoryInterface
{
    /** @var array<int,class-string<AiServiceInterface>|AiServiceInterface> */
    private array $services = [];

    /**
     * @param ContainerInterface $container 
     * @return void 
     */
    public function __construct(
        private ContainerInterface $container
    ) {}

    #[Override]
    public function list(string $name): Traversable
    {
        foreach ($this->services as $index => $service) {
            if (is_string($service)) {
                $service = $this->container->get($service);

                if (!($service instanceof AiServiceInterface)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Service "%s" is not an instance of "%s".',
                            $service::class,
                            AiServiceInterface::class
                        )
                    );
                }

                $this->services[$index] = $service;
            }

            if ($service instanceof $name) {
                yield $service;
            }
        }
    }

    #[Override]
    public function create(
        string $name,
        Model $model
    ): AiServiceInterface {
        foreach ($this->services as $index => $service) {
            if (is_string($service)) {
                $service = $this->container->get($service);

                if (!($service instanceof AiServiceInterface)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Service "%s" is not an instance of "%s".',
                            $service::class,
                            AiServiceInterface::class
                        )
                    );
                }

                $this->services[$index] = $service;
            }

            if (!($service instanceof $name)) {
                continue;
            }

            if ($service->supportsModel($model)) {
                return $service;
            }
        }

        throw new \RuntimeException(
            sprintf(
                'No service found for model %s.',
                $model->value
            )
        );
    }

    /**
     * @param class-string<AiServiceInterface>|AiServiceInterface $service
     * @return AiServiceFactory 
     */
    public function register(string|AiServiceInterface $service): self
    {
        $this->services[] = $service;
        return $this;
    }
}
