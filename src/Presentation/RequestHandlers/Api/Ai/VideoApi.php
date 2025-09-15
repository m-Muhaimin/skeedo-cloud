<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Ai;

use Ai\Application\Commands\GenerateVideoCommand;
use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Resources\Api\VideoResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/videos', method: RequestMethod::POST)]
class VideoApi extends AiServicesApi implements
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

        $cmd = new GenerateVideoCommand(
            $ws,
            $user,
            $payload->model
        );

        foreach ($payload as $key => $value) {
            $cmd->param($key, $value);
        }

        foreach ($request->getUploadedFiles()['frames'] ?? [] as $frame) {
            $cmd->frame($frame);
        }

        try {
            /** @var VideoEntity */
            $video = $this->dispatcher->dispatch($cmd);
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

        return new JsonResponse(new VideoResource($video));
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'model' => 'required|string',
            'prompt' => 'required|string'
        ]);
    }
}
