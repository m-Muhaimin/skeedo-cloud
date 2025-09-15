<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Ai;

use Ai\Application\Commands\GenerateCompositionCommand;
use Ai\Domain\Entities\CompositionEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Resources\Api\CompositionResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/compositions', method: RequestMethod::POST)]
class CompositionsApi extends AiServicesApi implements
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

        $payload = $request->getParsedBody();

        $cmd = new GenerateCompositionCommand(
            $ws,
            $user,
            $payload->model
        );

        if (isset($payload->prompt)) {
            $cmd->param('prompt', $payload->prompt);
        }

        if (isset($payload->tags)) {
            $cmd->param('tags', $payload->tags);
        }

        if (isset($payload->instrumental)) {
            $cmd->param('instrumental', $payload->instrumental);
        }

        try {
            /** @var CompositionEntity[] */
            $compositions = $this->dispatcher->dispatch($cmd);
        } catch (InsufficientCreditsException $th) {
            throw new HttpException(
                'Insufficient credits',
                StatusCode::FORBIDDEN
            );
        } catch (ApiException $th) {
            throw new UnprocessableEntityException(
                $th->getMessage(),
                previous: $th
            );
        }

        $data = [];
        foreach ($compositions as $composition) {
            $data[] = new CompositionResource($composition);
        }

        return new JsonResponse($data);
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'model' => 'required|string',
            'prompt' => 'string',
            'tags' => 'string',
            'instrumental' => 'boolean',
        ]);
    }
}
