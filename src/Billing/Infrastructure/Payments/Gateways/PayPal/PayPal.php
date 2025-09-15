<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\PayPal;

use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Entities\PlanSnapshotEntity;
use Billing\Infrastructure\Payments\OffsitePaymentGatewayInterface;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Infrastructure\Payments\Exceptions\PaymentException;
use Billing\Infrastructure\Payments\Helper;
use Billing\Infrastructure\Payments\WebhookHandlerInterface;
use DateTime;
use Easy\Container\Attributes\Inject;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Shared\Infrastructure\Atributes\BuiltInAspect;
use Symfony\Component\Intl\Currencies;

#[BuiltInAspect]
class PayPal implements OffsitePaymentGatewayInterface
{
    public const LOOKUP_KEY = 'paypal';

    public function __construct(
        private Client $client,
        private UriFactoryInterface $factory,
        private Helper $helper,

        #[Inject('option.paypal.is_enabled')]
        private bool $isEnabled = false,

        #[Inject('option.site.name')]
        private ?string $brandName = null,

        #[Inject('option.paypal.currency')]
        private ?string $currency = null,
    ) {}

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getName(): string
    {
        return 'PayPal';
    }

    public function getLogo(): string
    {
        return file_get_contents(__DIR__ . '/logo.svg');
    }

    public function getButtonBackgroundColor(): string
    {
        return '#0070ba';
    }

    public function getButtonTextColor(): string
    {
        return '#ffffff';
    }

    public function purchase(OrderEntity $order): UriInterface
    {
        if ($order->getPlan()->getBillingCycle()->isRecurring()) {
            return $this->createSubscription($order);
        } else {
            return $this->createOrder($order);
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
        $this->client->sendRequest(
            'POST',
            "/v1/billing/subscriptions/" . $id . "/cancel",
            [
                'reason' => 'Other'
            ]
        );
    }

    public function getWebhookHandler(): string|WebhookHandlerInterface
    {
        return WebhookRequestHandler::class;
    }

    private function createSubscription(OrderEntity $order): UriInterface
    {
        $plan = $this->findOrCreatePlan($order);
        $trial_days = $order->getTrialPeriodDays()->value;

        $body = [
            'plan_id' => $plan->id,
            'quantity' => 1,
            'custom_id' => 'ORDER-' . (string) $order->getId()->getValue(),
            'application_context' => [
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => $this->helper->generateReturnUrl($order, self::LOOKUP_KEY),
                'cancel_url' => $this->helper->generateCancelUrl($order, self::LOOKUP_KEY)
            ]
        ];

        if ($this->brandName) {
            $body['application_context']['brand_name'] = $this->brandName;
        }

        $startTime = $order->getCreatedAt();
        if ($trial_days && $trial_days > 0) {
            $startTime = new DateTime($startTime->format('c') . " + $trial_days days");
            $body['start_time'] = $startTime->format('c');
        }

        $resp = $this->client->sendRequest('POST', '/v1/billing/subscriptions', $body);

        if ($resp->getStatusCode() != 201) {
            throw new PaymentException('Failed to create subscription');
        }

        $subscription = json_decode($resp->getBody()->getContents());

        // Filter links to find the approval URL
        $approvalUrl = array_filter(
            $subscription->links,
            fn($link) => $link->rel == 'approve'
        );

        if (empty($approvalUrl)) {
            throw new PaymentException('Failed to create subscription');
        }

        return $this->factory->createUri($approvalUrl[0]->href);
    }

    private function createOrder(OrderEntity $order): UriInterface
    {
        list($amount, $currency) = $this->helper->convert(
            $order->getTotalPrice(),
            $order->getCurrencyCode(),
            $this->currency
        );

        list($subtotal, $currency) = $this->helper->convert(
            $order->getSubtotal(),
            $order->getCurrencyCode(),
            $this->currency
        );

        $fraction_digits = Currencies::getFractionDigits($currency->value);

        /**
         * The value, which might be:
         * An integer for currencies like JPY that are not typically fractional.
         * A decimal fraction for currencies like TND that are subdivided into 
         * thousandths.
         */
        $value = number_format(
            $amount->value / 10 ** $fraction_digits,
            $fraction_digits,
            '.',
            ''
        );

        $breakdown = [
            'item_total' => [
                'currency_code' => $currency->value,
                'value' => number_format(
                    $subtotal->value / 10 ** $fraction_digits,
                    $fraction_digits,
                    '.',
                    ''
                )
            ]
        ];

        $coupon = $order->getCoupon();
        if ($coupon) {
            $discounted = $coupon->calculateDiscountedAmount($subtotal->value);
            $breakdown['discount'] = [
                'currency_code' => $currency->value,
                'value' => number_format(
                    ($subtotal->value - $discounted) / 10 ** $fraction_digits,
                    $fraction_digits,
                    '.',
                    ''
                )
            ];
        }

        $body = [
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency->value,
                        'value' => $value,
                        'breakdown' => $breakdown
                    ],
                    'description' => $order->getPlan()->getTitle()->value,
                    'custom_id' => 'ORDER-' . (string) $order->getId()->getValue(),
                ]
            ],
            'intent' => 'CAPTURE',
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'CONTINUE',
                        'return_url' => $this->helper->generateReturnUrl($order, self::LOOKUP_KEY),
                        'cancel_url' => $this->helper->generateCancelUrl($order, self::LOOKUP_KEY)
                    ]
                ]
            ]
        ];

        if ($this->brandName) {
            $body['payment_source']['paypal']['experience_context']['brand_name'] = $this->brandName;
        }

        $resp = $this->client->sendRequest('POST', '/v2/checkout/orders', $body);

        if ($resp->getStatusCode() != 200) {
            throw new PaymentException('Failed to create order');
        }

        $order = json_decode($resp->getBody()->getContents());

        // Filter links to find the approval URL
        $approvalUrl = array_values(array_filter(
            $order->links,
            fn($link) => $link->rel == 'payer-action' || $link->rel == 'approve'
        ));

        if (empty($approvalUrl)) {
            throw new PaymentException('Failed to create order');
        }

        return $this->factory->createUri($approvalUrl[0]->href);
    }

    private function findOrCreatePlan(
        OrderEntity $order
    ): object {
        $product = $this->findOrCreateProduct($order->getPlan());
        $snapshot = $order->getPlan();
        $coupon = $order->getCoupon();

        list($amount, $currency) = $this->helper->convert(
            $snapshot->getPrice(),
            $order->getCurrencyCode(),
            $this->currency
        );

        $page = 1;
        while (true) {
            $resp = $this->client->sendRequest(
                'GET',
                "/v1/billing/plans/",
                params: [
                    'product_id' => $product->id,
                    'page' => $page,
                    'page_size' => 20,
                ]
            );

            if ($resp->getStatusCode() != 200) {
                break;
            }

            $list = json_decode($resp->getBody()->getContents());
            foreach ($list->plans as $plan) {
                if ($plan->status != 'ACTIVE') {
                    continue;
                }

                if (!isset($plan->description)) {
                    continue;
                }

                $key = (string) $snapshot->getId()->getValue();
                if ($coupon) {
                    $key .= ' / ' . (string) $coupon->getId()->getValue();
                }

                if ($plan->description !== $key) {
                    continue;
                }

                return $plan;
            }

            $hasMore = array_values(array_filter(
                $list->links,
                fn($link) => $link->rel == 'next'
            ));


            if (!empty($hasMore)) {
                $page++;
                continue;
            }

            break;
        }

        $billingCycles = [];
        $sequence = 1;
        $fractionDigits = Currencies::getFractionDigits($currency->value);

        if ($coupon) {
            $cycle = $coupon->getCycleCount()->value;
            $discountedAmount = $coupon->calculateDiscountedAmount(
                $snapshot->getPrice()->value
            );

            $billingCycles[] = [
                'tenure_type' => 'TRIAL',
                'sequence' => $sequence++,
                'total_cycles' => is_null($cycle) ? 999 : $cycle,
                'pricing_scheme' => [
                    'fixed_price' => [
                        'currency_code' => $currency->value,
                        'value' => number_format(
                            $discountedAmount / 10 ** $fractionDigits,
                            $fractionDigits,
                            '.',
                            ''
                        ),
                    ],
                ],
                'frequency' => [
                    'interval_unit' => 'DAY',
                    'interval_count' =>
                    $snapshot->getBillingCycle() == BillingCycle::YEARLY ? 365 : 30,
                ],
            ];
        }

        $billingCycles[] = [
            'tenure_type' => 'REGULAR',
            'sequence' => $sequence++,
            'total_cycles' => 0,

            'pricing_scheme' => [
                'fixed_price' => [
                    'currency_code' => $currency->value,
                    'value' => number_format(
                        (int) $amount->value / 10 ** $fractionDigits,
                        $fractionDigits,
                        '.',
                        ''
                    ),
                ],
            ],

            'frequency' => [
                'interval_unit' => 'DAY',

                'interval_count' =>
                $snapshot->getBillingCycle() == BillingCycle::YEARLY ? 365 : 30,
            ],
        ];

        $body = [
            'product_id' => $product->id,
            'name' => $snapshot->getTitle()->value . ' / ' . ucfirst($snapshot->getBillingCycle()->value),
            'status' => 'ACTIVE',
            'description' => (string) $snapshot->getId()->getValue(),
            'billing_cycles' => $billingCycles,
            'payment_preferences' => [
                'auto_bill_outstanding' => true,
            ]
        ];

        if ($coupon) {
            $body['name'] .= ' / ' . $coupon->getCode()->value;
            $body['description'] .= ' / ' . (string)$coupon->getId()->getValue();
        }

        $resp = $this->client->sendRequest('POST', '/v1/billing/plans', $body);
        $contents = json_decode($resp->getBody()->getContents());

        if ($resp->getStatusCode() != 201) {
            throw new PaymentException(
                'Failed to create plan. '
                    . ($contents->message ?? '')
                    . ($contents->details[0]->description ?? '')
            );
        }

        return  $contents;
    }

    private function findOrCreateProduct(
        PlanSnapshotEntity $snapshot
    ): object {
        $id = $snapshot->getPlan() ? $snapshot->getPlan()->getId() : $snapshot->getId();

        // Find the product
        $resp = $this->client->sendRequest('GET', "/v1/catalogs/products/" . $id->getValue());

        if ($resp->getStatusCode() == 200) {
            return json_decode($resp->getBody()->getContents());
        }

        $body = [
            'id' => (string) $id->getValue(),
            'name' => $snapshot->getTitle()->value,
            'type' => 'SERVICE',
            'category' => 'SOFTWARE'
        ];

        if ($snapshot->getDescription()->value) {
            $body['description'] = $snapshot->getDescription()->value;
        }

        $resp = $this->client->sendRequest('POST', '/v1/catalogs/products', $body);
        $contents = json_decode($resp->getBody()->getContents());

        if ($resp->getStatusCode() != 201) {
            throw new PaymentException(
                'Failed to create product. '
                    . ($contents->message ?? '')
                    . ($contents->details[0]->description ?? '')
            );
        }

        return  $contents;
    }

    private function completeSubscriptionPurchase(
        OrderEntity $order,
        array $params = []
    ): string {
        if (!isset($params['subscription_id'])) {
            throw new PaymentException('Missing parameter: subscription_id');
        }

        // Read subscription
        $resp = $this->client->sendRequest(
            'GET',
            "/v1/billing/subscriptions/" . $params['subscription_id']
        );

        if ($resp->getStatusCode() != 200) {
            throw new PaymentException('Failed to read subscription');
        }

        $subscription = json_decode($resp->getBody()->getContents());

        if ($subscription->custom_id != 'ORDER-' . (string) $order->getId()->getValue()) {
            throw new PaymentException('Subscription does not match order');
        }

        return $subscription->id;
    }

    private function completeOrderPurchase(
        OrderEntity $order,
        array $params = []
    ): string {
        if (!isset($params['token'])) {
            throw new PaymentException('Missing parameter: token');
        }

        // Read order
        $resp = $this->client->sendRequest(
            'GET',
            "/v2/checkout/orders/" . $params['token']
        );

        if ($resp->getStatusCode() != 200) {
            throw new PaymentException('Failed to read order');
        }

        $po = json_decode($resp->getBody()->getContents());

        if ($po->purchase_units[0]->custom_id != 'ORDER-' . (string) $order->getId()->getValue()) {
            throw new PaymentException('Order does not match order');
        }

        // Capture the order
        $resp = $this->client->sendRequest(
            'POST',
            "/v2/checkout/orders/" . $params['token'] . "/capture"
        );

        if ($resp->getStatusCode() != 201) {
            throw new PaymentException('Failed to capture order');
        }

        return $params['token'];
    }
}
