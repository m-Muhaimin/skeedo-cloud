<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Currency;

use Billing\Infrastructure\Currency\RateProviders\NullRateProvider;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Traversable;

class RateProviderCollection implements RateProviderCollectionInterface
{
    private array $providers = [];

    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    #[Override]
    public function getIterator(): Traversable
    {
        foreach ($this->providers as $key => $provider) {
            if ($key == NullRateProvider::LOOKUP_KEY) {
                continue;
            }

            if (is_string($provider)) {
                $provider = $this->container->get($provider);

                if (!($provider instanceof RateProviderInterface)) {
                    continue;
                }

                $this->providers[$key] = $provider;
            }


            yield $key => $provider;
        }
    }

    #[Override]
    public function add(
        string $key,
        string|RateProviderInterface $provider
    ): static {
        $this->providers[$key] = $provider;
        return $this;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Override]
    public function get(string $key): ?RateProviderInterface
    {
        if (!array_key_exists($key, $this->providers)) {
            return null;
        }

        $provider = $this->providers[$key];

        if (is_string($provider)) {
            $provider = $this->container->get($provider);

            if (!($provider instanceof RateProviderInterface)) {
                return null;
            }

            $this->providers[$key] = $provider;
        }

        return $provider;
    }
}
