<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Plans;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Billing\Application\Commands\UpdatePlanCommand;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\Status;
use Override;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\PlanResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;

#[Route(path: '/[uuid:id]', method: RequestMethod::PATCH)]
#[Route(path: '/[uuid:id]', method: RequestMethod::POST)]
class UpdatePlanRequestHandler extends PlanApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    /**
     * @throws ValidationException
     * @throws NotFoundException
     * @throws NoHandlerFoundException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        try {
            $cmd = new UpdatePlanCommand(
                $request->getAttribute('id')
            );

            if (property_exists($payload, 'title')) {
                $cmd->setTitle($payload->title);
            }

            if (property_exists($payload, 'price')) {
                $cmd->setPrice((int) $payload->price);
            }

            if (property_exists($payload, 'billing_cycle')) {
                $cmd->setBillingCycle($payload->billing_cycle);
            }

            if (property_exists($payload, 'description')) {
                $cmd->setDescription($payload->description);
            }

            if (property_exists($payload, 'credit_count')) {
                $cmd->setCreditCount(
                    $payload->credit_count === null
                        ? $payload->credit_count
                        : (int) $payload->credit_count
                );
            }

            if (property_exists($payload, 'member_cap')) {
                $cmd->setMemberCap(
                    $payload->member_cap === null
                        ? $payload->member_cap
                        : (int) $payload->member_cap
                );
            }

            if (property_exists($payload, 'superiority')) {
                $cmd->setSuperiority((int) $payload->superiority);
            }

            if (property_exists($payload, 'status')) {
                $cmd->setStatus((int) $payload->status);
            }

            if (property_exists($payload, 'is_featured')) {
                $cmd->setIsFeatured((bool) $payload->is_featured);
            }

            if (property_exists($payload, 'icon')) {
                $cmd->setIcon($payload->icon);
            }

            if (property_exists($payload, 'features')) {
                $list = array_filter(array_map('trim', explode(",", $payload->features)), 'strlen');
                $cmd->setFeatureList(...$list);
            }

            if (property_exists($payload, 'config')) {
                $cmd->setConfig(json_decode(json_encode($payload->config), true));
            }

            if (property_exists($payload, 'update_snapshots')) {
                $cmd->updateSnapshots = (bool) $payload->update_snapshots;
            }

            /** @var PlanEntity $plan */
            $plan = $this->dispatcher->dispatch($cmd);
        } catch (PlanNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(new PlanResource($plan));
    }

    /**
     * @throws ValidationException
     */
    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'title' => 'string|max:255',
            'price' => 'integer|min:0',
            'billing_cycle' => 'string|in:' . implode(",", array_map(
                fn(BillingCycle $type) => $type->value,
                BillingCycle::cases()
            )),
            'description' => 'string',
            'credit_count' => 'integer|min:0',
            'member_cap' => 'integer|min:0|nullable',
            'superiority' => 'integer|min:0',
            'status' => 'integer|in:' . implode(",", array_map(
                fn(Status $type) => $type->value,
                Status::cases()
            )),
            'is_featured' => 'integer|in:0,1',
            'icon' => 'string',
            'features' => 'string',
            'config' => 'array',
            'update_snapshots' => 'boolean',
        ]);
    }
}
