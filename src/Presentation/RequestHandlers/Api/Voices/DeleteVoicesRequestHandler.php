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
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Voice\Application\Commands\DeleteVoiceCommand;
use Voice\Domain\Exceptions\VoiceNotFoundException;

#[Route(path: '/[uuid:id]', method: RequestMethod::DELETE)]
class DeleteVoicesRequestHandler extends VoiceApi implements
    RequestHandlerInterface
{
    public function __construct(
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

        $id = $request->getAttribute('id');
        $cmd = new DeleteVoiceCommand($id);

        try {
            $this->dispatcher->dispatch($cmd);
        } catch (VoiceNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new EmptyResponse();
    }

    private function validateRequest(ServerRequestInterface $request): void
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $this->ac->denyUnlessGranted(
            Permission::VOICE_DELETE,
            $user,
            $request->getAttribute("id")
        );
    }
}
