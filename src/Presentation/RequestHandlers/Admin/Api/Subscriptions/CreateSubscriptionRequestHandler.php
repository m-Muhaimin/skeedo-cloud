<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Subscriptions;

use Billing\Application\Commands\CreateSubscriptionCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\NotSubscriptionPlanException;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Resources\Admin\Api\SubscriptionResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

#[Route(path: '/', method: RequestMethod::POST)]
class CreateSubscriptionRequestHandler extends SubscriptionApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $cmd = new CreateSubscriptionCommand(
            $payload->workspace_id,
            $payload->plan_id
        );

        try {
            /** @var SubscriptionEntity */
            $sub = $this->dispatcher->dispatch($cmd);
        } catch (WorkspaceNotFoundException $th) {
            throw new HttpException(
                'Workspace not found',
                StatusCode::UNPROCESSABLE_ENTITY,
                'workspace_id',
                $th,
            );
        } catch (PlanNotFoundException $th) {
            throw new HttpException(
                'Plan not found',
                StatusCode::UNPROCESSABLE_ENTITY,
                'plan_id',
                $th,
            );
        } catch (NotSubscriptionPlanException $th) {
            throw new HttpException(
                'Invalid plan for subscription',
                StatusCode::UNPROCESSABLE_ENTITY,
                'plan_id',
                $th,
            );
        }

        return new JsonResponse(
            new SubscriptionResource($sub, ["workspace", "plan"]),
            StatusCode::CREATED
        );
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'plan_id' => 'required|uuid',
            'workspace_id' => 'required|uuid',
        ]);
    }
}
