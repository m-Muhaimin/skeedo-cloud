<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Ai;

use Ai\Application\Commands\GenerateSpeechCommand;
use Ai\Application\Commands\IsolateVoiceCommand;
use Ai\Domain\Entities\IsolatedVoiceEntity;
use Ai\Domain\Entities\SpeechEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Resources\Api\IsolatedVoiceResource;
use Presentation\Resources\Api\SpeechResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/isolated-voices', method: RequestMethod::POST)]
class VoiceIsolationApi extends AiServicesApi implements
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

        /** @var UploadedFileInterface */
        $file = $request->getUploadedFiles()['file'];

        $cmd = new IsolateVoiceCommand(
            $ws,
            $user,
            $file,
            'elevenlabs',
        );

        try {
            /** @var IsolatedVoiceEntity */
            $voice = $this->dispatcher->dispatch($cmd);
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
            new IsolatedVoiceResource($voice)
        );
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'file' => 'required|uploaded_file',
        ]);
    }
}
