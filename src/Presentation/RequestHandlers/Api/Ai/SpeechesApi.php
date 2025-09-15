<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Ai;

use Ai\Application\Commands\GenerateSpeechCommand;
use Ai\Domain\Entities\SpeechEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Resources\Api\SpeechResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/speeches', method: RequestMethod::POST)]
class SpeechesApi extends AiServicesApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,
        private AiServiceFactoryInterface $factory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $cmd = new GenerateSpeechCommand(
            $ws,
            $user,
            $payload->voice_id,
        );

        $cmd->param('prompt', $payload->prompt);

        try {
            /** @var SpeechEntity */
            $speech = $this->dispatcher->dispatch($cmd);
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

        return new JsonResponse(
            new SpeechResource($speech)
        );
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'prompt' => 'required|string',
            'voice_id' => 'required|string'
        ]);
    }
}
