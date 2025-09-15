<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Plugins;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Plugin\Application\Commands\UpdatePluginCommand;
use Plugin\Domain\Exceptions\PluginNotFoundException;
use Plugin\Domain\PluginWrapper;
use Plugin\Domain\ValueObjects\Status;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\PluginResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[:vendor]/[:package]', method: RequestMethod::PUT)]
#[Route(path: '/[:vendor]/[:package]', method: RequestMethod::POST)]
class UpdatePluginRequesthandler extends PluginsApi implements
    RequestHandlerInterface
{
    public function __construct(
        public Validator $validator,
        public Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        $vendor = $request->getAttribute('vendor');
        $package = $request->getAttribute('package');
        $payload = (object) $request->getParsedBody();

        $cmd = new UpdatePluginCommand($vendor . "/" . $package);

        if (property_exists($payload, 'status')) {
            $cmd->setStatus($payload->status);
        }

        try {
            /** @var PluginWrapper */
            $pw = $this->dispatcher->dispatch($cmd);
        } catch (PluginNotFoundException $th) {
            throw new NotFoundException(
                previous: $th
            );
        }

        return new JsonResponse(new PluginResource($pw->context));
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'status' => 'string|in:' . implode(",", array_map(
                fn (Status $type) => $type->value,
                Status::cases()
            )),
        ]);
    }
}
