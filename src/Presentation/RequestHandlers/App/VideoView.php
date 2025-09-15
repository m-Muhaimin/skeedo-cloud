<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Ai\Application\Commands\ReadLibraryItemCommand;
use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Infrastructure\Services\CostCalculator;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Option\Infrastructure\OptionResolver;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\AccessControls\Permission;
use Presentation\Resources\Api\VideoResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\Services\ModelRegistry;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/video/[uuid:id]?', method: RequestMethod::GET)]
class VideoView extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private LibraryItemAccessControl $ac,
        private ModelRegistry $registry,
        private OptionResolver $resolver,
        private CostCalculator $calc,

        #[Inject('option.features.video.is_enabled')]
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
                $video = $this->dispatcher->dispatch($cmd);

                if (
                    !($video instanceof VideoEntity)
                    || !$this->ac->isGranted(Permission::LIBRARY_ITEM_READ, $user, $video)
                ) {
                    return new RedirectResponse('/app/video');
                }
            } catch (LibraryItemNotFoundException $th) {
                return new RedirectResponse('/app/video');
            }

            $data['video'] = new VideoResource($video);
        }

        $data['services'] = $this->getServices($request);

        return new ViewResponse(
            '/templates/app/video.twig',
            $data
        );
    }

    private function getServices(ServerRequestInterface $request): array
    {
        $granted = [];

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);
        $sub = $ws->getSubscription();

        if ($sub) {
            $granted = $sub->getPlan()->getConfig()->models;
        }

        $services = [];
        foreach ($this->registry['directory'] as $service) {
            if (!$this->resolver->canResolve('option.' . $service['key'])) {
                continue;
            }

            $models = array_filter(
                $service['models'],
                fn($model) => $model['type'] === 'video'
                    && ($model['enabled'] ?? false)
            );

            array_walk(
                $models,
                function (&$model) use ($granted) {
                    unset($model['rates']);
                    $model['granted'] = $granted[$model['key']] ?? false;
                    $model['estimation'] = $this->calc->estimate($model['key'])->value;
                }
            );
            $models = array_values($models);

            if (count($models) === 0) {
                continue;
            }

            $service['models'] = $models;

            $accepted = ['key', 'name', 'icon', 'models'];
            $services[] = array_intersect_key($service, array_flip($accepted));
        }

        return $services;
    }
}
