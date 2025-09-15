<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Route(path: '/library/[documents|code-documents|images|videos|transcriptions|speeches|conversations|isolated-voices|classifications|compositions:view]?', method: RequestMethod::GET)]
class LibraryView extends AppView implements
    RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $view = $request->getAttribute('view') ?? 'index';

        return new ViewResponse(
            '/templates/app/library/' . $view . '.twig'
        );
    }
}
