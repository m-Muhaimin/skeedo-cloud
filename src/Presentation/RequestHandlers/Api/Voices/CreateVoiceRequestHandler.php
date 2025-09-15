<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Voices;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Api\VoiceResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use User\Domain\Entities\UserEntity;
use Voice\Application\Commands\CreateVoiceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/', method: RequestMethod::POST)]
class CreateVoiceRequestHandler extends VoiceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,

        #[Inject('option.features.voiceover.is_enabled')]
        private bool $isEnabled = false,

        #[Inject('option.features.voiceover.is_voice_cloning_enabled')]
        private bool $isVoiceCloningEnabled = false,

        #[Inject('option.features.voiceover.cloning_model')]
        private string $model = 'speechify'
    ) {}

    /**
     * @throws ValidationException
     * @throws NoHandlerFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isEnabled || !$this->isVoiceCloningEnabled) {
            throw new NotFoundException();
        }

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);
        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        if ($ws->isVoiceCapExceeded()) {
            throw new HttpException(
                'Voice cloning limit reached for the workspace.',
                StatusCode::FORBIDDEN
            );
        }

        $this->validateRequest($request);

        /** @var UploadedFileInterface */
        $file = $request->getUploadedFiles()['file'];

        $payload = (object) $request->getParsedBody();

        $cmd = new CreateVoiceCommand(
            $ws,
            $user,
            $this->model,
            $payload->name,
            $file,
        );

        $voice = $this->dispatcher->dispatch($cmd);

        return new JsonResponse(
            new VoiceResource($voice),
            StatusCode::CREATED
        );
    }

    /**
     * @throws ValidationException
     */
    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'name' => 'required|string',
            'file' => 'required|uploaded_file',
            'consent' => 'integer|in:1',
        ]);
    }
}
