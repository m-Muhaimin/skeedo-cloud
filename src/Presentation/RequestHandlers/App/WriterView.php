<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Ai\Application\Commands\ReadLibraryItemCommand;
use Ai\Domain\Entities\DocumentEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\AccessControls\Permission;
use Presentation\Resources\Api\DocumentResource;
use Presentation\Resources\Api\PresetResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Preset\Application\Commands\ReadPresetCommand;
use Preset\Domain\Entities\PresetEntity;
use Preset\Domain\Exceptions\PresetNotFoundException;
use Preset\Domain\Placeholder\ParserService;
use Preset\Domain\Placeholder\PlaceholderFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;

#[Route(path: '/writer/[uuid:id]?', method: RequestMethod::GET)]
class WriterView  extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ParserService $parser,
        private PlaceholderFactory $factory,
        private LibraryItemAccessControl $ac,

        #[Inject('option.features.writer.is_enabled')]
        private bool $isEnabled = false
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isEnabled) {
            return new RedirectResponse('/app');
        }

        $id = $request->getAttribute('id');

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $data = [];
        $data['placeholders'] = [];

        if (!$id) {
            return new ViewResponse(
                '/templates/app/writer.twig',
                $data
            );
        }

        // First check if the ID belongs to a document
        $cmd = new ReadLibraryItemCommand($id);

        try {
            $doc = $this->dispatcher->dispatch($cmd);

            if (
                !($doc instanceof DocumentEntity)
                || !$this->ac->isGranted(Permission::LIBRARY_ITEM_READ, $user, $doc)
            ) {
                return new RedirectResponse('/app/writer');
            }

            $preset = $doc->getPreset();
            $data['document'] = new DocumentResource($doc);
        } catch (LibraryItemNotFoundException $th) {
            // Document not found, we'll check if it's a preset
            $doc = null;
        }

        if (!$doc) {
            $cmd = new ReadPresetCommand($id);

            try {
                /** @var PresetEntity $preset */
                $preset = $this->dispatcher->dispatch($cmd);
            } catch (PresetNotFoundException $th) {
                // Neither document nor preset found
                return new RedirectResponse('/app/writer');
            }
        }

        if ($preset) {
            $data['preset'] = new PresetResource($preset);
            $data['placeholders'] = $this->parser->parse(
                $preset->getTemplate()->value
            );
        }

        return new ViewResponse(
            '/templates/app/writer.twig',
            $data
        );
    }
}
