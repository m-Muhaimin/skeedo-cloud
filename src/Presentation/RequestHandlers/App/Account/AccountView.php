<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App\Account;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Path;
use Easy\Router\Attributes\Route;
use Easy\Router\Priority;
use Presentation\RequestHandlers\App\AppView;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Path('/account')]
#[Route(
    path: '/[overview|email|password|profile|memories:name]?',
    method: RequestMethod::GET,
    priority: Priority::HIGH
)]
class AccountView extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        #[Inject('config.locale.locales')]
        private array $locales = []
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name') ?? 'overview';

        return new ViewResponse(
            '/templates/app/account/' . $name . '.twig',
            [
                'locales' => $this->locales,
            ]
        );
    }
}
