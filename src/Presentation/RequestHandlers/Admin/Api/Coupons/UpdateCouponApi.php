<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Coupons;

use Billing\Application\Commands\UpdateCouponCommand;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Exceptions\CouponNotFoundException;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\Status;
use Easy\Router\Attributes\Route;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\CouponResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[uuid:id]', method: RequestMethod::PATCH)]
#[Route(path: '/[uuid:id]', method: RequestMethod::POST)]
class UpdateCouponApi extends CouponApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $cmd = new UpdateCouponCommand(
            $request->getAttribute('id')
        );

        if (property_exists($payload, 'title')) {
            $cmd->setTitle($payload->title);
        }

        if (property_exists($payload, 'status')) {
            $cmd->setStatus((int)$payload->status);
        }

        if (property_exists($payload, 'redemption_limit')) {
            $cmd->setRedemptionLimit(
                is_null($payload->redemption_limit)
                    ? null
                    : (int) $payload->redemption_limit
            );
        }

        if (property_exists($payload, 'billing_cycle')) {
            $cmd->setBillingCycle($payload->billing_cycle);
        }

        if (property_exists($payload, 'starts_at')) {
            $cmd->setStartsAt(
                is_null($payload->starts_at)
                    ? null
                    : (string) $payload->starts_at
            );
        }

        if (property_exists($payload, 'expires_at')) {
            $cmd->setExpiresAt(
                is_null($payload->expires_at)
                    ? null
                    : (string) $payload->expires_at
            );
        }

        if (property_exists($payload, 'plan')) {
            $cmd->setPlan($payload->plan);
        }

        try {
            /** @var CouponEntity $coupon */
            $coupon = $this->dispatcher->dispatch($cmd);
        } catch (CouponNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(
            new CouponResource($coupon),
            StatusCode::CREATED
        );
    }

    private function validateRequest(ServerRequestInterface $request): void
    {
        $this->validator->validateRequest($request, [
            'title' => 'string|max:255',
            'status' => 'integer|in:' . implode(",", array_map(
                fn(Status $type) => $type->value,
                Status::cases()
            )),
            'redemption_limit' => 'nullable|integer|min:0',
            'billing_cycle' => 'nullable|string|in:' . implode(",", array_map(
                fn(BillingCycle $type) => $type->value,
                BillingCycle::cases()
            )),
            'starts_at' => 'nullable|date:U',
            'expires_at' => 'nullable|date:U',
            'plan' => 'nullable|string|uuid',
        ]);
    }
}
