<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Option\Infrastructure\OptionResolver;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\Response\ViewResponse;
use Preset\Domain\Placeholder\ParserService;
use Preset\Domain\Placeholder\PlaceholderFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\Services\ModelRegistry;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/models', method: RequestMethod::GET)]
class ModelsView extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ParserService $parser,
        private PlaceholderFactory $factory,
        private LibraryItemAccessControl $ac,
        private ModelRegistry $registry,
        private OptionResolver $resolver,

        #[Inject('option.credit_rate')]
        private array $rates = [],

        #[Inject('option.features.chat.is_enabled')]
        private bool $isEnabled = false
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {

        $data = [];
        $data['services'] = $this->getServices($request);

        return new ViewResponse(
            '/templates/app/models.twig',
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

            $models = array_filter(
                $service['models'],
                fn($model) => ($model['enabled'] ?? false)
            );

            array_walk(
                $models,
                function (&$model) use ($granted) {
                    $model['granted'] = $granted[$model['key']] ?? false;
                    if (isset($model['rates'])) {
                        array_walk($model['rates'], function (&$rate) {
                            $rate['value'] = $this->rates[$rate['key']] ?? 0;
                        });
                    }
                }
            );
            $models = array_values($models);

            if (count($models) === 0) {
                continue;
            }

            $service['models'] = $models;
            if (isset($service['rates'])) {
                array_walk($service['rates'], function (&$rate) {
                    $rate['value'] = $this->rates[$rate['key']] ?? 0;
                });
            }

            $accepted = ['key', 'name', 'icon', 'models', 'rates'];


            $services[] = array_intersect_key($service, array_flip($accepted));
        }

        return $services;
    }
}
