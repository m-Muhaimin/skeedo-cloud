<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bootstrappers;

use Application;
use Billing\Infrastructure\Currency\RateProviderCollectionInterface;
use Billing\Infrastructure\Currency\RateProviderInterface;
use Billing\Infrastructure\Currency\RateProviders\NullRateProvider;
use Easy\Container\Attributes\Inject;
use Override;
use Shared\Infrastructure\BootstrapperInterface;
use Throwable;

class PreferencesBootstrapper implements BootstrapperInterface
{
    public function __construct(
        private Application $app,

        private RateProviderCollectionInterface $rateProviderCollection,

        #[Inject('option.currency.provider')]
        private ?string $currencyProviderKey = null

    ) {
    }

    #[Override]
    public function bootstrap(): void
    {
        $this->defineRateProvider();
    }

    private function defineRateProvider(): void
    {
        $provider = null;
        $collection = $this->rateProviderCollection;

        try {
            $provider = $collection->get($this->currencyProviderKey);
        } catch (Throwable $th) {
            // Currency provider not found
        }

        if (!$provider) {
            /** @var RateProviderInterface */
            $provider = $collection->get(NullRateProvider::LOOKUP_KEY);
        }

        $this->app->set(
            RateProviderInterface::class,
            $provider
        );
    }
}
