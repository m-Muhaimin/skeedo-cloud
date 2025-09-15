<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Plugins;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Plugin\Application\Commands\InstallPluginCommand;
use Plugin\Domain\Services\PluginValidationService;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Resources\Admin\Api\PluginResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Throwable;

#[Route(path: '/', method: RequestMethod::POST)]
class InstallPluginRequestHandler extends PluginsApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private PluginValidationService $service,

        #[Inject('config.dirs.artifacts')]
        private string $artifactsDir,

        #[Inject('config.enable_debugging')]
        private bool $debug = false,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $files = $request->getUploadedFiles();

        /** @var UploadedFileInterface|null $file */
        $file = array_values($files)[0] ?? null;

        if (!$file) {
            throw new UnprocessableEntityException('Upload failed');
        }

        // Check if the extension is .zip
        $filename = $file->getClientFilename();
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'zip') {
            throw new UnprocessableEntityException('Please upload a zip file');
        }

        $baseDir = $this->artifactsDir;

        try {
            if (!file_exists($baseDir)) {
                mkdir($baseDir, 0777, true);
            }

            $filename = uniqid() . '.zip';
            $file->moveTo($baseDir . '/' . $filename);
        } catch (Throwable $th) {
            throw new UnprocessableEntityException(
                $this->debug && $th->getMessage() ? $th->getMessage() : 'Failed to install plugin',
                previous: $th
            );
        }

        try {
            $context = $this->service->validateZipFile(
                $baseDir . '/' . $filename
            );

            $cmd = new InstallPluginCommand($context->name);
            $this->dispatcher->dispatch($cmd);
        } catch (Throwable $th) {
            unlink($baseDir . '/' . $filename);

            throw new UnprocessableEntityException(
                $th->getMessage() ?: 'Invalid plugin',
                previous: $th
            );
        }

        unlink($baseDir . '/' . $filename);
        return new JsonResponse(new PluginResource($context));
    }
}
