<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments;

/**
 * PaymentGatewayInterface represents an interface for a payment gateway.
 * It defines methods for checking if the payment gateway is enabled,
 * getting the name and logo of the payment gateway, purchasing an order,
 * completing a purchase, and canceling a subscription.
 */
interface OffsitePaymentGatewayInterface extends PaymentGatewayInterface
{
    /**
     * Get the logo of the payment gateway.
     *
     * @return string The URL, SVG source or base64-encoded data of the payment 
     * gateway's logo
     */
    public function getLogo(): string;

    /**
     * Returns the background color of the payment button.
     *
     * @return string The background color of the payment button.
     */
    public function getButtonBackgroundColor(): string;

    /**
     * Returns the text color of the payment button.
     *
     * @return string The text color of the payment button.
     */
    public function getButtonTextColor(): string;
}
