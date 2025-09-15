<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Account;

use Affiliate\Application\Commands\UpdateAffiliateCommand;
use Affiliate\Domain\ValueObjects\PayoutMethod;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Path;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Resources\Api\UserResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;

#[Path('/affiliate')]
#[Route(path: '/', method: RequestMethod::PUT)]
#[Route(path: '/', method: RequestMethod::POST)]
class AffiliateApi extends AccountApi implements RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);
        $this->validateRequest($request);

        /** @var object{payout_method:'paypal'|'bank_transfer',paypal_email:string,bank_requisites:string} */
        $payload = $request->getParsedBody();

        $cmd = new UpdateAffiliateCommand(
            $user
        );

        if (property_exists($payload, 'payout_method')) {
            $cmd->setPayoutMethod($payload->payout_method);
        }

        if (property_exists($payload, 'paypal_email')) {
            $cmd->setPayPalEmail($payload->paypal_email);
        }

        if (property_exists($payload, 'bank_requisites')) {
            $cmd->setBankRequisites($payload->bank_requisites);
        }

        $this->dispatcher->dispatch($cmd);

        return new JsonResponse(new UserResource($user));
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'payout_method' => 'string|in:' . implode(",", array_map(
                fn(PayoutMethod $type) => $type->value,
                PayoutMethod::cases()
            )),
            'paypal_email' => 'string|email',
            'bank_requisites' => 'string',
        ]);
    }
}
