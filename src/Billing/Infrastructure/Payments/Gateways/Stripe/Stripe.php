<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\Stripe;

use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Entities\OrderEntity;
use Billing\Infrastructure\Payments\Exceptions\PaymentException;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\DiscountType;
use Billing\Infrastructure\Payments\CardPaymentGatewayInterface;
use Billing\Infrastructure\Payments\Helper;
use Billing\Infrastructure\Payments\PurchaseToken;
use Billing\Infrastructure\Payments\WebhookHandlerInterface;
use Easy\Container\Attributes\Inject;
use Shared\Infrastructure\Atributes\BuiltInAspect;
use Stripe\Coupon;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Price;
use Stripe\Product;
use Stripe\SetupIntent;
use Stripe\Subscription;

#[BuiltInAspect]
class Stripe implements CardPaymentGatewayInterface
{
    public const LOOKUP_KEY = 'stripe';

    public function __construct(
        private Client $client,
        private Helper $helper,

        #[Inject('option.stripe.is_enabled')]
        private bool $isEnabled = false,

        #[Inject('option.stripe.currency')]
        private ?string $currency = null,
    ) {}

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getName(): string
    {
        return 'Stripe';
    }

    public function purchase(
        OrderEntity $order
    ): PurchaseToken {
        try {
            if ($order->getPlan()->getBillingCycle()->isRecurring()) {
                return $this->createSubscription($order);
            } else {
                return $this->createOrder($order);
            }
        } catch (ApiErrorException $th) {
            throw new PaymentException($th->getMessage(), $th->getCode(), $th);
        }
    }

    public function completePurchase(
        OrderEntity $order,
        array $params = []
    ): string {
        if ($order->getPlan()->getBillingCycle()->isRecurring()) {
            return $this->completeSubscriptionPurchase($order, $params);
        } else {
            return $this->completeOrderPurchase($order, $params);
        }
    }

    public function cancelSubscription(string $id): void
    {
        try {
            // Retrieve the subscription
            $subs = $this->client->subscriptions
                ->retrieve($id);

            $subs->cancel();
        } catch (ApiErrorException $th) {
            throw new PaymentException($th->getMessage(), $th->getCode(), $th);
        }
    }

    public function getWebhookHandler(): string|WebhookHandlerInterface
    {
        return WebhookRequestHandler::class;
    }

    private function createSubscription(
        OrderEntity $order
    ): PurchaseToken {
        $customer = $this->findOrCreateCustomer($order);
        $price = $this->findOrCreatePrice($order);
        $coupon = $order->getCoupon()
            ? $this->findOrCreateCoupon($order->getCoupon(), $price->currency)
            : null;

        $params = [
            'customer' => $customer->id,
            'items' => [[
                'price' => $price->id,
            ]],
            'metadata' => [
                'order_id' => (string) $order->getId()->getValue(),
            ],
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => [
                'save_default_payment_method' => 'on_subscription'
            ],
            'expand' => [
                'latest_invoice.payment_intent',
                'pending_setup_intent'
            ]
        ];

        $trial_period_days = $order->getTrialPeriodDays()->value;
        if ($trial_period_days && $trial_period_days > 0) {
            $params['trial_period_days'] = $trial_period_days;
        }

        if ($coupon) {
            $params['discounts'] = [[
                'coupon' => $coupon->id,
            ]];
        }

        $sub = $this->client->subscriptions->create($params);

        return new PurchaseToken(
            $sub->latest_invoice->payment_intent->client_secret
                ?? $sub->pending_setup_intent->client_secret
        );
    }

    private function createOrder(
        OrderEntity $order
    ): PurchaseToken {
        $customer = $this->findOrCreateCustomer($order);

        list($amount, $currency) = $this->helper->convert(
            $order->getTotalPrice(),
            $order->getCurrencyCode(),
            $this->currency
        );

        $params = [
            'amount' => $amount->value,
            'currency' => $currency->value,
            'customer' => $customer->id,
            'metadata' => [
                'order_id' => (string) $order->getId()->getValue(),
            ],
            'description' => $order->getPlan()->getTitle()->value,
            'capture_method' => 'automatic',
        ];

        $intent = $this->client->paymentIntents->create($params);
        return new PurchaseToken($intent->client_secret);
    }

    private function findOrCreateCustomer(
        OrderEntity $order
    ): Customer {
        $ws = $order->getWorkspace();
        $user = $ws->getOwner();

        $customer = null;

        try {
            $customers = $this->client->customers->search([
                'query' => 'email:"' . $user->getEmail()->value . '" AND metadata["user_id"]:"' . (string) $user->getId()->getValue() . '"',
            ]);

            $customer = $customers->data[0] ?? null;

            if ($customer && !$customer->isDeleted()) {
                return $customer;
            }
        } catch (ApiErrorException $th) {
            // Error: The search feature is temporarily unavailable in your region.

            // Iterate over all customers
            $cursor = null;
            while (true) {
                $params = ['limit' => 100];
                if ($cursor) {
                    $params['starting_after'] = $cursor;
                }

                $customers = $this->client->customers->all($params);


                foreach ($customers->data as $c) {
                    if (
                        $c->email == $user->getEmail()->value
                        && isset($c->metadata->user_id)
                        && $c->metadata->user_id == (string) $user->getId()->getValue()
                        && !$c->isDeleted()
                    ) {
                        return $c;
                    }

                    $cursor = $c->id;
                }

                if (!$customers->has_more) {
                    break;
                }
            }
        }

        $params = [
            'email' => $user->getEmail()->value,
            'name' => $user->getFirstName()->value . ' ' . $user->getLastName()->value,
            'metadata' => [
                'user_id' => (string) $user->getId()->getValue(),
            ],
        ];

        if ($ws->getAddress()) {
            $address = [];

            if ($ws->getAddress()->city) {
                $address['city'] = $ws->getAddress()->city;
            }

            if ($ws->getAddress()->country) {
                $address['country'] = $ws->getAddress()->country;
            }

            if ($ws->getAddress()->line1) {
                $address['line1'] = $ws->getAddress()->line1;
            }

            if ($ws->getAddress()->line2) {
                $address['line2'] = $ws->getAddress()->line2;
            }

            if ($ws->getAddress()->zip) {
                $address['postal_code'] = $ws->getAddress()->zip;
            }

            if ($ws->getAddress()->state) {
                $address['state'] = $ws->getAddress()->state;
            }

            if ($address) {
                $params['address'] = $address;
            }
        }

        $customer = $this->client->customers->create($params);

        return $customer;
    }

    private function findOrCreatePrice(
        OrderEntity $order
    ): Price {
        $product = $this->findOrCreateProduct($order);
        $plan = $order->getPlan();
        $id = (string) $plan->getId()->getValue();

        list($amount, $currency) = $this->helper->convert(
            $plan->getPrice(),
            $order->getCurrencyCode(),
            $this->currency
        );

        try {
            $prices = $this->client->prices->search(
                [
                    'query' => 'product:"' . $product->id . '" AND metadata["plan_snapshot_id"]:"' . $id . '"',
                ]
            );

            $price = $prices->data[0] ?? null;

            if (
                $price
                && !$price->isDeleted()
                && strtolower($price->currency) == strtolower($currency->value)
            ) {
                return $price;
            }
        } catch (ApiErrorException $th) {
            // Error: The search feature is temporarily unavailable in your region.

            // Iterate over all prices
            $cursor = null;
            while (true) {
                $params = ['limit' => 100];
                if ($cursor) {
                    $params['starting_after'] = $cursor;
                }

                $prices = $this->client->prices->all($params);

                foreach ($prices->data as $p) {
                    if (
                        $p->product == $product->id
                        && isset($p->metadata->plan_snapshot_id)
                        && $p->metadata->plan_snapshot_id == $id
                        && !$p->isDeleted()
                        && strtolower($p->currency) == strtolower($currency->value)
                    ) {
                        return $p;
                    }

                    $cursor = $p->id;
                }

                if (!$prices->has_more) {
                    break;
                }
            }
        }

        $params = [
            'currency' => $currency->value,
            'metadata' => [
                'plan_snapshot_id' => (string) $plan->getId()->getValue(),
            ],
            'product' => $product->id,
            'recurring' => [
                'interval' => 'day',
                'interval_count' => $plan->getBillingCycle() == BillingCycle::YEARLY ? 365 : 30,
            ],
            'unit_amount' => $amount->value,
        ];

        $price = $this->client->prices->create($params);

        return $price;
    }

    private function findOrCreateProduct(
        OrderEntity $order
    ): Product {
        $plan = $order->getPlan()->getPlan() ?? $order->getPlan();
        $id = (string) $plan->getId()->getValue();

        try {
            $product = $this->client->products->retrieve($id);
        } catch (ApiErrorException $th) {
            if ($th->getHttpStatus() != 404) {
                throw $th;
            }

            $product = null;
        }

        if (!$product || $product->isDeleted()) {
            $params = [
                'id' => $id,
                'name' => $plan->getTitle()->value,
                'shippable' => false,
            ];

            if ($plan->getDescription()->value) {
                $params['description'] = $plan->getDescription()->value;
            }

            $product = $this->client->products->create($params);
        }

        return $product;
    }

    private function findOrCreateCoupon(
        CouponEntity $entity,
        string $currency
    ): Coupon {
        $currency = strtoupper($currency);
        $code = $entity->getCode()->value;
        $type = $entity->getDiscountType();

        if ($type == DiscountType::FIXED) {
            $code = $code . '-' . $currency;
        }

        try {
            $coupon = $this->client->coupons->retrieve($code);
            if (!$coupon->isDeleted()) {
                return $coupon;
            }
        } catch (ApiErrorException $th) {
            if ($th->getHttpStatus() != 404) {
                throw $th;
            }
        }

        $params = [
            'id' => $code,
            'name' => $entity->getCode()->value, // Original code
            'metadata' => [
                'id' => (string) $entity->getId()->getValue(),
            ],
        ];

        if ($type == DiscountType::FIXED) {
            $params['amount_off'] = $entity->getAmount()->value;
            $params['currency'] = $currency;
        } else {
            $params['percent_off'] = $entity->getAmount()->value / 100;
        }

        $cycle = $entity->getCycleCount()->value;
        if (is_null($cycle)) {
            $params['duration'] = 'forever';
        } elseif ($cycle == 1) {
            $params['duration'] = 'once';
        } elseif ($cycle > 1) {
            $params['duration'] = 'repeating';
            $params['duration_in_months'] = ($entity->getBillingCycle() == BillingCycle::YEARLY ? 12 : 1) * $cycle;
        }

        $coupon = $this->client->coupons->create($params);

        return $coupon;
    }

    private function completeSubscriptionPurchase(
        OrderEntity $order,
        array $params = []
    ): string {
        if (!isset($params['payment_intent']) && !isset($params['setup_intent'])) {
            throw new PaymentException('Missing parameter: payment_intent or setup_intent');
        }

        try {
            if (isset($params['payment_intent'])) {
                $resp = $this->client->paymentIntents->retrieve($params['payment_intent'], [
                    'expand' => [
                        'latest_charge.invoice.subscription'
                    ],
                ]);

                if ($resp->status != PaymentIntent::STATUS_SUCCEEDED) {
                    throw new PaymentException('Payment intent not succeeded');
                }

                $subscription = $resp->latest_charge->invoice->subscription;
            } else {
                $resp = $this->client->setupIntents->retrieve($params['setup_intent'], [
                    'expand' => [
                        'customer',
                        'customer.subscriptions'
                    ],
                ]);

                if ($resp->status != SetupIntent::STATUS_SUCCEEDED) {
                    throw new PaymentException('Setup intent not succeeded');
                }

                $subs = $resp->customer->subscriptions->all();
                $subscription = null;

                foreach ($subs as $sub) {
                    if ($sub->metadata->order_id == (string) $order->getId()->getValue()) {
                        $subscription = $sub;
                        break;
                    }
                }
            }
        } catch (ApiErrorException $th) {
            throw new PaymentException($th->getMessage(), $th->getCode(), $th);
        }

        if (!$subscription) {
            throw new PaymentException('Subscription not found');
        }

        if ($subscription->metadata->order_id != (string) $order->getId()->getValue()) {
            throw new PaymentException('Subscription does not match order');
        }

        if (!in_array(
            $subscription->status,
            [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING]
        )) {
            throw new PaymentException('Subscription not active');
        }

        return $subscription->id;
    }

    private function completeOrderPurchase(
        OrderEntity $order,
        array $params = []
    ): string {
        if (!isset($params['payment_intent'])) {
            throw new PaymentException('Missing parameter: payment_intent');
        }

        try {
            $resp = $this->client->paymentIntents->retrieve($params['payment_intent'], [
                'expand' => [
                    'latest_charge'
                ],
            ]);
        } catch (ApiErrorException $th) {
            throw new PaymentException($th->getMessage(), $th->getCode(), $th);
        }

        $charge = $resp->latest_charge;

        if ($resp->metadata->order_id != (string) $order->getId()->getValue()) {
            throw new PaymentException('Subscription does not match order');
        }

        return $charge->id;
    }
}
