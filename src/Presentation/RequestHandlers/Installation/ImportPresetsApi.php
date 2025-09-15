<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Installation;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Commands\ImportPresetsCommand;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

#[Route(path: '/presets/import', method: RequestMethod::POST)]
class ImportPresetsApi extends InstallationApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $input = new ArrayInput([]);
            $output = new BufferedOutput();

            $cmd = new ImportPresetsCommand($this->dispatcher);
            $cmd->run($input, $output);
        } catch (Throwable $th) {
            throw new UnprocessableEntityException($th->getMessage());
        }

        return new EmptyResponse();
    }
}
