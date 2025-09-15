<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Ai\Application\Commands\ReadLibraryItemCommand;
use Ai\Domain\Entities\CompositionEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\AccessControls\Permission;
use Presentation\Resources\Api\CompositionResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;

#[Route(path: '/composer/[uuid:id]?', method: RequestMethod::GET)]
class ComposerView extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private LibraryItemAccessControl $ac,

        #[Inject('option.features.composer.is_enabled')]
        private bool $isEnabled = false
    ) {}

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
                $composition = $this->dispatcher->dispatch($cmd);

                if (
                    !($composition instanceof CompositionEntity)
                    || !$this->ac->isGranted(Permission::LIBRARY_ITEM_READ, $user, $composition)
                ) {
                    return new RedirectResponse('/app/composer');
                }
            } catch (LibraryItemNotFoundException $th) {
                return new RedirectResponse('/app/composer');
            }

            $data['composition'] = new CompositionResource($composition);
        }

        return new ViewResponse(
            '/templates/app/composer.twig',
            $data
        );
    }
}
