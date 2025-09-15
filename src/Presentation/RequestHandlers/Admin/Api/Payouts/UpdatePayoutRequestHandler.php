<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Payouts;

use Affiliate\Application\Commands\ApprovePayoutCommand;
use Affiliate\Application\Commands\ReadPayoutCommand;
use Affiliate\Application\Commands\RejectPayoutCommand;
use Affiliate\Domain\Entities\PayoutEntity;
use Affiliate\Domain\ValueObjects\Status;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Exceptions\HttpException;
use Presentation\Resources\Admin\Api\PayoutResource;
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
class UpdatePayoutRequestHandler extends PayoutsApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    /**
     * @throws ValidationException
     * @throws HttpException
     * @throws NoHandlerFoundException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $status = property_exists($payload, 'status') ? $payload->status : null;

        $cmd = match ($status) {
            'approved' => new ApprovePayoutCommand($id),
            'rejected' => new RejectPayoutCommand($id),
            default => new ReadPayoutCommand($id)
        };


        /** @var PayoutEntity $payout */
        $payout = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new PayoutResource($payout, ['affiliate', 'affiliate.user']));
    }

    /**
     * @throws ValidationException
     */
    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'status' => 'string|in:' . implode(",", array_map(
                fn(Status $type) => $type->value,
                Status::cases()
            ))
        ]);
    }
}
