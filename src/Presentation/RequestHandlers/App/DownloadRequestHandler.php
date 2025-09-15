<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use File\Application\Commands\ReadFileCommand;
use File\Domain\Exceptions\FileNotFoundException;
use File\Domain\Repositories\FileRepositoryInterface;
use File\Infrastructure\FileService;
use Presentation\Response\DownloadResponse;
use Presentation\Response\RedirectResponse;
use Presentation\Response\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/download/[uuid:id]/[i:time]/[a:token]', method: RequestMethod::GET)]
class DownloadRequestHandler extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        private FileService $fileService,
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $time = $request->getAttribute('time');
        $token = $request->getAttribute('token');

        // Validate that the link is not older than 60 minutes
        $currentTime = time();
        $linkAge = $currentTime - $time;

        if ($linkAge > 3600) { // 3600 seconds = 60 minutes
            return new RedirectResponse('/app');
        }

        try {
            $cmd = new ReadFileCommand($id);
            $file = $this->dispatcher->dispatch($cmd);
        } catch (FileNotFoundException $e) {
            return new RedirectResponse('/app');
        }

        if (!$file) {
            return new RedirectResponse('/app');
        }

        // Validate the token
        $expectedToken = hash('sha256', $time . $file->getId() . env('JWT_TOKEN'));

        if (!hash_equals($expectedToken, $token)) {
            return new RedirectResponse('/app');
        }

        // Get file service to read file contents

        try {
            $fileContents = $this->fileService->getFileContents($file);

            // Get filename from object key
            $filename = basename($file->getObjectKey()->value);

            // Create response with file contents
            $response = new DownloadResponse(
                $fileContents,
                $filename,
                $file->getSize()->value
            );

            return $response;
        } catch (\Exception $e) {
            // If file cannot be read, redirect to app
            throw $e;
            return new RedirectResponse('/app');
        }
    }
}
