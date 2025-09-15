<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use Billing\Domain\Entities\OrderEntity;
use JsonSerializable;
use Presentation\Resources\CurrencyResource;
use Presentation\Resources\DateTimeResource;

class OrderResource implements JsonSerializable
{
    public function __construct(
        private OrderEntity $order,
        private array $extend = []
    ) {}

    public function jsonSerialize(): array
    {
        $order = $this->order;
        $coupon = $order->getCoupon();

        $output = [
            'id' => $order->getId(),
            'currency' => new CurrencyResource($order->getCurrencyCode()),
            'payment_gateway' => $order->getPaymentGateway(),
            'external_id' => $order->getExternalId(),
            'status' => $order->getStatus(),
            'created_at' => new DateTimeResource($order->getCreatedAt()),
            'updated_at' => new DateTimeResource($order->getUpdatedAt()),
            'plan' => new PlanSnapshotResource($order->getPlan()),
            'subtotal' => $order->getSubtotal(),
            'discount' => $order->getDiscount(),
            'total' => $order->getTotalPrice(),
            'coupon' => $coupon ? new CouponResource($coupon) : null,
        ];

        if (in_array('workspace', $this->extend)) {
            // Workspace extends (Seatch $this->extend for values start with "workspace." and remove "workspace." from the value)
            $extend = array_map(
                fn($v) => str_replace('workspace.', '', $v),

                array_filter(
                    $this->extend,
                    fn($v) => str_starts_with($v, 'workspace.')
                )
            );


            $output['workspace'] = new WorkspaceResource(
                $order->getWorkspace(),
                $extend
            );
        }

        if (in_array('subscription', $this->extend)) {
            $output['subscription'] = $order->getSubscription()
                ? new SubscriptionResource($order->getSubscription()) : null;
        }

        return $output;
    }
}
