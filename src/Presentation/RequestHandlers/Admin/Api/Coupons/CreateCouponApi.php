<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Coupons;

use Billing\Application\Commands\CreateCouponCommand;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\Status;
use Easy\Router\Attributes\Route;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Presentation\Resources\Admin\Api\CouponResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Presentation\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use ValueError;
use TypeError;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;

#[Route(path: '/', method: RequestMethod::POST)]
class CreateCouponApi extends CouponApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    /**
     * @throws ValidationException
     * @throws ValueError
     * @throws TypeError
     * @throws NoHandlerFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        $payload = (object) $request->getParsedBody();

        $cmd = new CreateCouponCommand(
            $payload->title,
            $payload->code,
            (int) $payload->amount,
            $payload->discount_type,
            !property_exists($payload, 'cycle_count') || is_null($payload->cycle_count) ? null : (int) $payload->cycle_count,
        );

        if (property_exists($payload, 'plan')) {
            $cmd->setPlan($payload->plan);
        }

        if (property_exists($payload, 'billing_cycle')) {
            $cmd->setBillingCycle($payload->billing_cycle);
        }

        if (property_exists($payload, 'status')) {
            $cmd->setStatus((int)$payload->status);
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

        if (property_exists($payload, 'redemption_limit')) {
            $cmd->setRedemptionLimit(
                is_null($payload->redemption_limit)
                    ? null
                    : (int) $payload->redemption_limit
            );
        }

        /** @var CouponEntity $coupon */
        $coupon = $this->dispatcher->dispatch($cmd);

        return new JsonResponse(
            new CouponResource($coupon),
            StatusCode::CREATED
        );
    }

    private function validateRequest(ServerRequestInterface $request): void
    {
        $this->validator->validateRequest($request, [
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
            'discount_type' => 'required|string|in:percentage,fixed',
            'cycle_count' => 'nullable|integer|min:0',
            'plan' => 'nullable|string|uuid',
            'billing_cycle' => 'nullable|string|in:' . implode(",", array_map(
                fn(BillingCycle $type) => $type->value,
                BillingCycle::cases()
            )),
            'status' => 'integer|in:' . implode(",", array_map(
                fn(Status $type) => $type->value,
                Status::cases()
            )),
            'starts_at' => 'nullable|date:U',
            'expires_at' => 'nullable|date:U',
            'redemption_limit' => 'nullable|integer|min:0',
        ]);
    }
}
