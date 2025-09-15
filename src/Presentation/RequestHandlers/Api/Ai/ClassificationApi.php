<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Ai;

use Ai\Application\Commands\GenerateClassificationCommand;
use Ai\Domain\Entities\ClassificationEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Resources\Api\ClassificationResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/classifications', method: RequestMethod::POST)]
class ClassificationApi extends AiServicesApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $cmd = new GenerateClassificationCommand(
            $ws,
            $user,
            'omni-moderation-latest',
            $payload->input
        );

        try {
            /** @var ClassificationEntity */
            $classification = $this->dispatcher->dispatch($cmd);
        } catch (InsufficientCreditsException $th) {
            throw new HttpException(
                'Insufficient credits',
                StatusCode::FORBIDDEN
            );
        }

        return new JsonResponse(new ClassificationResource(
            $classification
        ), StatusCode::CREATED);
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'input' => 'required|string',
        ]);
    }
}
