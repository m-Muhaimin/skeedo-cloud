<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Ai\Application\Commands\ReadLibraryItemCommand;
use Ai\Domain\Entities\CodeDocumentEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\AccessControls\Permission;
use Presentation\Resources\Api\CodeDocumentResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;

#[Route(path: '/coder/[uuid:id]?', method: RequestMethod::GET)]
class CoderView extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private LibraryItemAccessControl $ac,

        #[Inject('option.features.coder.is_enabled')]
        private bool $isEnabled = false
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isEnabled) {
            return new RedirectResponse('/app');
        }

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $id = $request->getAttribute('id');
        $data = [];

        if ($id) {
            $cmd = new ReadLibraryItemCommand($id);

            try {
                $doc = $this->dispatcher->dispatch($cmd);

                if (
                    !($doc instanceof CodeDocumentEntity)
                    || !$this->ac->isGranted(Permission::LIBRARY_ITEM_READ, $user, $doc)
                ) {
                    return new RedirectResponse('/app/coder');
                }
            } catch (LibraryItemNotFoundException $th) {
                return new RedirectResponse('/app/coder');
            }

            $data['code_document'] = new CodeDocumentResource($doc);
        }

        return new ViewResponse(
            '/templates/app/coder.twig',
            $data
        );
    }
}
