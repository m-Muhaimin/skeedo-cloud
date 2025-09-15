<?php

declare(strict_types=1);

namespace Billing\Infrastructure;

use Application;
use Billing\Domain\Repositories\CouponRepositoryInterface;
use Billing\Infrastructure\Payments\PaymentGatewayFactoryInterface;
use Billing\Domain\Repositories\OrderRepositoryInterface;
use Billing\Domain\Repositories\PlanRepositoryInterface;
use Billing\Domain\Repositories\PlanSnapshotRepositoryInterface;
use Billing\Domain\Repositories\SubscriptionRepositoryInterface;
use Billing\Infrastructure\Currency\Exchange;
use Billing\Infrastructure\Currency\ExchangeInterface;
use Billing\Infrastructure\Currency\RateProviderCollection;
use Billing\Infrastructure\Currency\RateProviderCollectionInterface;
use Billing\Infrastructure\Currency\RateProviders\CurrencyApi\CurrencyApi;
use Billing\Infrastructure\Currency\RateProviders\NullRateProvider;
use Billing\Infrastructure\Payments\Gateways\BankTransfer\BankTransfer;
use Billing\Infrastructure\Payments\Gateways\ManualPayment\ManualPayment;
use Billing\Infrastructure\Payments\Gateways\PayPal\PayPal;
use Billing\Infrastructure\Payments\Gateways\Stripe\Stripe;
use Billing\Infrastructure\Payments\PaymentGatewayFactory;
use Billing\Infrastructure\Repositories\DoctrineOrm\CouponRepository;
use Billing\Infrastructure\Repositories\DoctrineOrm\OrderRepository;
use Billing\Infrastructure\Repositories\DoctrineOrm\PlanRepository;
use Billing\Infrastructure\Repositories\DoctrineOrm\PlanSnapshotRepository;
use Billing\Infrastructure\Repositories\DoctrineOrm\SubscriptionRepository;
use Override;
use Psr\Container\ContainerInterface;
use Shared\Infrastructure\BootstrapperInterface;

class BillingModuleBootstrapper implements BootstrapperInterface
{
    public function __construct(
        private Application $app,
        private PaymentGatewayFactory $factory,
        private ContainerInterface $container
    ) {}

    #[Override]
    public function bootstrap(): void
    {
        // Register repository implementations
        $this->app
            ->set(
                PlanRepositoryInterface::class,
                PlanRepository::class
            )
            ->set(
                PlanSnapshotRepositoryInterface::class,
                PlanSnapshotRepository::class
            )
            ->set(
                SubscriptionRepositoryInterface::class,
                SubscriptionRepository::class
            )
            ->set(
                OrderRepositoryInterface::class,
                OrderRepository::class
            )
            ->set(
                CouponRepositoryInterface::class,
                CouponRepository::class
            );

        // Register payment gateway implementations
        $this->app->set(
            PaymentGatewayFactoryInterface::class,
            $this->factory
        );

        $this->registerBuiltInPaymentGateways();
        $this->registerCurrencyLayer();
    }

    private function registerBuiltInPaymentGateways(): void
    {
        $this->factory
            ->register(Stripe::LOOKUP_KEY, Stripe::class)
            ->register(PayPal::LOOKUP_KEY, PayPal::class)
            ->register(BankTransfer::LOOKUP_KEY, BankTransfer::class)
            ->register(ManualPayment::LOOKUP_KEY, ManualPayment::class);
    }

    private function registerCurrencyLayer(): void
    {
        $this->app->set(
            ExchangeInterface::class,
            Exchange::class
        );

        $collection = new RateProviderCollection($this->container);
        $collection
            ->add(NullRateProvider::LOOKUP_KEY, NullRateProvider::class)
            ->add(CurrencyApi::LOOKUP_KEY, CurrencyApi::class);

        $this->app->set(
            RateProviderCollectionInterface::class,
            $collection
        );
    }
}
