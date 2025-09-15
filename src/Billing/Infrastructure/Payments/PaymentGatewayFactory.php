<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments;

use Billing\Infrastructure\Payments\Exceptions\GatewayNotFoundException;
use Billing\Infrastructure\Payments\PaymentGatewayFactoryInterface;
use Billing\Infrastructure\Payments\PaymentGatewayInterface;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Traversable;

class PaymentGatewayFactory implements PaymentGatewayFactoryInterface
{
    /** @var array<string,class-string<PaymentGatewayInterface>|PaymentGatewayInterface> */
    private array $gateways = [];

    public function __construct(
        private ContainerInterface $container,

        private ?string $cardPaymentGatewayKey = 'stripe',
    ) {
    }

    /**
     * @return Traversable<string,PaymentGatewayInterface>
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function getIterator(): Traversable
    {
        foreach ($this->gateways as $key => $gateway) {
            if ($key === PaymentGatewayFactoryInterface::CARD_PAYMENT_GATEWAY_KEY) {
                continue;
            }

            yield $key => $this->create($key);
        }
    }

    /**
     * @inheritDoc
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function create(string $key): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$key])) {
            throw new GatewayNotFoundException($key);
        }

        $gateway = $this->gateways[$key];

        if ($gateway instanceof PaymentGatewayInterface) {
            return $gateway;
        }

        $gateway = $this->resolveGateway($gateway);
        $this->gateways[$key] = $gateway;

        return $gateway;
    }

    #[Override]
    public function register(
        string $key,
        string|PaymentGatewayInterface $gateway
    ): static {
        $this->gateways[$key] = $gateway;

        if ($key == $this->cardPaymentGatewayKey) {
            $this->gateways[PaymentGatewayFactoryInterface::CARD_PAYMENT_GATEWAY_KEY] = $gateway;
        }

        return $this;
    }

    /**
     * @param string $gateway
     * @return PaymentGatewayInterface
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws RuntimeException
     */
    private function resolveGateway(string $gateway): PaymentGatewayInterface
    {
        if (is_string($gateway)) {
            $gateway = $this->container->get($gateway);
        }

        if (!($gateway instanceof PaymentGatewayInterface)) {
            throw new \RuntimeException(
                "Gateway {$gateway} is not an instance of "
                    . PaymentGatewayInterface::class
            );
        }

        return $gateway;
    }
}
