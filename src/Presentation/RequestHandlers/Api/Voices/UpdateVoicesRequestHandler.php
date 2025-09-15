<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Voices;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Exception;
use Override;
use Presentation\AccessControls\Permission;
use Presentation\AccessControls\VoiceAccessControl;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\VoiceResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Voice\Application\Commands\UpdateVoiceCommand;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\Exceptions\VoiceNotFoundException;

#[Route(path: '/[uuid:id]', method: RequestMethod::PATCH)]
#[Route(path: '/[uuid:id]', method: RequestMethod::POST)]
class UpdateVoicesRequestHandler extends VoiceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private VoiceAccessControl $ac,
        private Dispatcher $dispatcher,
    ) {}

    /**
     * @throws NotFoundException
     * @throws Exception
     * @throws HttpException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $id = $request->getAttribute('id');
        $cmd = new UpdateVoiceCommand($id);

        if (property_exists($payload, 'name')) {
            $cmd->setName($payload->name);
        }

        if (property_exists($payload, 'visibility')) {
            $cmd->setVisibility((int) $payload->visibility);
        }

        try {
            /** @var VoiceEntity */
            $voice = $this->dispatcher->dispatch($cmd);
        } catch (VoiceNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(new VoiceResource($voice));
    }

    private function validateRequest(ServerRequestInterface $request): void
    {
        $this->validator->validateRequest($request, [
            'name' => 'string',
            'visibility' => 'integer|in:0,1'
        ]);

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $this->ac->denyUnlessGranted(
            Permission::VOICE_EDIT,
            $user,
            $request->getAttribute("id")
        );
    }
}
