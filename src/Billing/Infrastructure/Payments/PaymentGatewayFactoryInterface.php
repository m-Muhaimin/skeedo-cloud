<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments;

use Billing\Infrastructure\Payments\Exceptions\GatewayNotFoundException;
use IteratorAggregate;

/** 
 * @extends IteratorAggregate<string,PaymentGatewayInterface>
 */
interface PaymentGatewayFactoryInterface extends IteratorAggregate
{
    public const CARD_PAYMENT_GATEWAY_KEY = 'card';

    /**
     * Creates a payment gateway instance based on the given gateway name.
     *
     * @param string $gateway The name of the gateway.
     * @return PaymentGatewayInterface The created payment gateway instance.
     * @throws GatewayNotFoundException
     */
    public function create(string $gateway): PaymentGatewayInterface;

    /**
     * Registers a payment gateway implementation.
     * 
     * @param string $key Unique key for the gateway
     * @param class-string<PaymentGatewayInterface>|PaymentGatewayInterface $gateway  
     * The gateway implementation or its class name.
     * @return static
     */
    public function register(
        string $key,
        string|PaymentGatewayInterface $gateway
    ): static;
}
