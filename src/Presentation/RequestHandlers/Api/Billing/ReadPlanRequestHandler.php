<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Billing;

use Billing\Application\Commands\ReadCouponCommand;
use Billing\Application\Commands\ReadPlanCommand;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Exceptions\CouponNotFoundException;
use Billing\Domain\Exceptions\InvalidCouponException;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\NotFoundException;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\RequestHandlers\Api\Billing\BillingApi;
use Presentation\Resources\Api\PlanResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/plans/[uuid:id]', method: RequestMethod::GET)]
class ReadPlanRequestHandler extends BillingApi implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $params = $request->getQueryParams();

        $cmd = new ReadPlanCommand($id);

        try {
            /** @var PlanEntity $plan */
            $plan = $this->dispatcher->dispatch($cmd);
        } catch (PlanNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        $code = $params['coupon'] ?? null;

        if ($code) {
            $cmd = new ReadCouponCommand($code);

            try {
                $coupon = $this->dispatcher->dispatch($cmd);
                $plan->applyCoupon($coupon);
            } catch (CouponNotFoundException $th) {
                throw new UnprocessableEntityException(
                    message: 'invalid_coupon',
                    param: 'coupon',
                    previous: $th
                );
            } catch (InvalidCouponException $th) {
                throw new UnprocessableEntityException(
                    message: $th->type->value,
                    param: 'coupon',
                    previous: $th
                );
            }
        }

        return new JsonResponse(new PlanResource($plan));
    }
}
