<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Currency;

use IteratorAggregate;

/**
 * @extends IteratorAggregate<string,RateProviderInterface>
 */
interface RateProviderCollectionInterface extends IteratorAggregate
{
    /**
     * @param string $key Lookup key
     * @param class-string<RateProviderInterface>|RateProviderInterface $provider
     */
    public function add(
        string $key,
        string|RateProviderInterface $provider
    ): static;


    public function get(string $key): ?RateProviderInterface;
}
