<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Options;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\Services\ModelRegistry;

#[Route(path: '/models', method: RequestMethod::POST)]
class ModelsApi extends OptionsApi implements
    RequestHandlerInterface
{
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private ModelRegistry $registry,

        #[Inject('config.dirs.root')]
        private string $root
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = json_decode(json_encode($request->getParsedBody()), true);

        $directory = $this->registry['directory'];

        foreach ($payload as $path => $data) {
            $parts = explode('.', $path, 2);

            if (count($parts) !== 2) {
                continue;
            }

            foreach ($directory as &$service) {
                if ($service['key'] !== $parts[0]) {
                    continue;
                }

                foreach ($service['models'] as &$model) {
                    if ($model['key'] !== $parts[1]) {
                        continue;
                    }

                    if (array_key_exists('enabled', $data)) {
                        $model['enabled'] = $data['enabled'];
                    }
                }
            }
        }

        $this->registry['directory'] = $directory;

        $this->registry->save();
        return new EmptyResponse();
    }
}
