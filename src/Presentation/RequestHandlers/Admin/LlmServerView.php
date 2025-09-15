<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Nonstandard\Uuid;
use Shared\Infrastructure\Services\ModelRegistry;
use Symfony\Component\Intl\Exception\MissingResourceException;

#[Route(path: '/settings/llms/[uuid:id]', method: RequestMethod::GET)]
#[Route(path: '/settings/llms', method: RequestMethod::GET)]
class LlmServerView extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    private array $llms = [];

    public function __construct(
        private ModelRegistry $registry,
    ) {
        $this->llms = array_filter($this->registry['directory'], function ($llm) {
            return $llm['custom'] ?? false;
        });

        $this->llms = array_values($this->llms);
    }

    /**
     * @throws MissingResourceException 
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $data = [
            "id" => Uuid::uuid4()->toString(),
        ];

        if ($id) {
            $llm = array_filter($this->llms, function ($llm) use ($id) {
                return $llm['key'] === $id;
            });

            if (empty($llm)) {
                return new RedirectResponse('/admin/settings');
            }

            $llm = array_values($llm);
            $data['llms'] = $llm[0];
            $data['id'] = $id;
        }

        return new ViewResponse(
            '/templates/admin/settings/llms.twig',
            $data
        );
    }
}
