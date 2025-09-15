<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments;

use Billing\Domain\Entities\OrderEntity;
use Billing\Infrastructure\Payments\Exceptions\PaymentException;
use Psr\Http\Message\UriInterface;

interface PaymentGatewayInterface
{
    /**
     * Check if the payment gateway is enabled.
     *
     * @return bool True if the payment gateway is enabled, false otherwise.
     */
    public function isEnabled(): bool;

    /**
     * Get the name of the payment gateway.
     *
     * @return string The name of the payment gateway.
     */
    public function getName(): string;

    /**
     * Purchase an order by charging for it or creating a link to the external 
     * payment page.
     *
     * This method is responsible for processing the payment for an order. It 
     * can either charge the customer for the order or create a link to an 
     * external payment page where the customer can complete the payment. The 
     * behavior depends on the order and the payment source provided.
     *
     * If a payment source is provided, the method will attempt to charge the 
     * customer using that payment source. If no payment source is provided, 
     * the method will create a link to an external payment page where the 
     * customer can make the payment.
     *
     * @param OrderEntity $order The order to be purchased.
     * @return UriInterface|PurchaseToken|string Returns either 
     *  - a UriInterface object representing the link to the external payment 
     * page 
     *  - a PurchaseToken object representing the purchase token for the order,
     * for example payment intent secret for Stripe
     *  - a string representing the subscription/charge created on the payment 
     * gateway
     * @throws PaymentException If an error occurs while processing the payment.
     */
    public function purchase(
        OrderEntity $order
    ): UriInterface|PurchaseToken|string;

    /**
     * Completes a purchase for the given order.
     *
     * @param OrderEntity $order The order entity to complete the purchase for.
     * @param array $params Additional parameters for completing the purchase.
     * @return string Returns a string representing the subscription/charge 
     * created on the payment gateway.
     * @throws PaymentException If an error occurs while completing the purchase.
     */
    public function completePurchase(
        OrderEntity $order,
        array $params = []
    ): string;

    /**
     * Cancels a subscription.
     *
     * @param string $id The ID of subscription to be cancelled. This is the ID 
     * in the payment gateway.
     * @return void
     * @throws PaymentException If an error occurs while cancelling the 
     * subscription.
     */
    public function cancelSubscription(string $id): void;

    /**
     * Get the request handler for the payment gateway webhook.
     *
     * @return class-string<WebhookHandlerInterface>|WebhookHandlerInterface The 
     * request handler for the payment gateway webhook.
     */
    public function getWebhookHandler(): string|WebhookHandlerInterface;
}
