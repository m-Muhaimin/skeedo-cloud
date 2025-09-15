<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Voices;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Presentation\Response\JsonResponse;
use Presentation\Resources\CountResource;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Voice\Application\Commands\CountVoicesCommand;

#[Route(path: '/count', method: RequestMethod::GET)]
class CountVoicesRequestHandler extends VoiceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    /**
     * @throws NoHandlerFoundException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new CountVoicesCommand();

        $params = (object) $request->getQueryParams();

        if (property_exists($params, 'workspace')) {
            $cmd->setWorkspace($params->workspace);
        }

        if (property_exists($params, 'status')) {
            $cmd->setStatus((int) $params->status);
        }

        if (property_exists($params, 'provider')) {
            $cmd->setProvider($params->provider);
        }

        if (property_exists($params, 'tone')) {
            $cmd->setTone($params->tone);
        }

        if (property_exists($params, 'use_case')) {
            $cmd->setUseCase($params->use_case);
        }

        if (property_exists($params, 'gender')) {
            $cmd->setGender($params->gender);
        }

        if (property_exists($params, 'accent')) {
            $cmd->setAccent($params->accent);
        }

        if (property_exists($params, 'language')) {
            $cmd->setLanguageCode($params->language);
        }

        if (property_exists($params, 'query') && $params->query) {
            $cmd->query = $params->query;
        }

        $count = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new CountResource($count));
    }
}
