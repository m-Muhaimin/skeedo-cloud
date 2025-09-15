<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Luma;

use Ai\Application\Commands\ReadLibraryItemCommand;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Middlewares\ExceptionMiddleware;
use Presentation\Response\EmptyResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Middleware(ExceptionMiddleware::class)]
#[Route(path: '/webhooks/luma/[uuid:id]', method: RequestMethod::POST)]
class WebhookRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ContainerInterface $container,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        // Find video by id
        $cmd = new ReadLibraryItemCommand($id);

        try {
            /** @var AbstractLibraryItemEntity $entity */
            $entity = $this->dispatcher->dispatch($cmd);
        } catch (LibraryItemNotFoundException $th) {
            throw new NotFoundException();
        }

        $contents = $request->getBody()->getContents();
        $data = json_decode($contents);

        if (!$data) {
            throw new HttpException(
                'Invalid request',
                StatusCode::BAD_REQUEST
            );
        }

        if (
            !isset($data->id)
            || $data->id !== $entity->getMeta('luma_id')
        ) {
            throw new HttpException(
                'Invalid request',
                StatusCode::BAD_REQUEST
            );
        }

        if ($entity instanceof VideoEntity) {
            $processor = $this->container->get(VideoWebhookProcessor::class);
            $processor($entity, $data);
        } else {
            throw new NotFoundException();
        }

        return new EmptyResponse();
    }
}
