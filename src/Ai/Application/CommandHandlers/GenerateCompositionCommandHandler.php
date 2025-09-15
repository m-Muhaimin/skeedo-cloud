<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\GenerateCompositionCommand;
use Ai\Domain\Composition\CompositionServiceInterface;
use Ai\Domain\Entities\CompositionEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Title;
use Billing\Domain\Events\CreditUsageEvent;
use Billing\Domain\ValueObjects\CreditCount;
use File\Domain\Entities\FileEntity;
use File\Domain\Entities\ImageFileEntity;
use File\Domain\ValueObjects\Height;
use File\Domain\ValueObjects\ObjectKey;
use File\Domain\ValueObjects\Size;
use File\Domain\ValueObjects\Storage;
use File\Domain\ValueObjects\Url;
use File\Domain\ValueObjects\Width;
use File\Infrastructure\BlurhashGenerator;
use Easy\Container\Attributes\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shared\Infrastructure\FileSystem\CdnInterface;
use User\Domain\Entities\UserEntity;
use User\Domain\Repositories\UserRepositoryInterface;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class GenerateCompositionCommandHandler
{
    public function __construct(
        private AiServiceFactoryInterface $factory,
        private WorkspaceRepositoryInterface $wsRepo,
        private UserRepositoryInterface $userRepo,
        private LibraryItemRepositoryInterface $repo,
        private CdnInterface $cdn,
        private EventDispatcherInterface $dispatcher,

        #[Inject('option.billing.negative_balance_enabled')]
        private bool $negativeBalance = false,
    ) {}

    /**
     * @return CompositionEntity[]
     */
    public function handle(GenerateCompositionCommand $cmd): array
    {
        $ws = $cmd->workspace instanceof WorkspaceEntity
            ? $cmd->workspace
            : $this->wsRepo->ofId($cmd->workspace);

        $user = $cmd->user instanceof UserEntity
            ? $cmd->user
            : $this->userRepo->ofId($cmd->user);

        if (
            !is_null($ws->getTotalCreditCount()->value)
            && (float) $ws->getTotalCreditCount()->value <= 0
        ) {
            throw new InsufficientCreditsException();
        }

        // Create service
        $service = $this->factory->create(
            CompositionServiceInterface::class,
            $cmd->model
        );

        // Generate isolated voice
        $resp = $service->generateComposition(
            $cmd->model,
            $cmd->params
        );

        $entities = [];
        foreach ($resp as $composition) {
            $content = '';
            while (!$composition->audioContent->eof()) {
                $content .= $composition->audioContent->read(1);
            }

            // Save output file to CDN
            $name = $this->cdn->generatePath('mp3', $ws, $user);
            $this->cdn->write($name, $content);

            $outputFile = new FileEntity(
                new Storage($this->cdn->getAdapterLookupKey()),
                new ObjectKey($name),
                new Url($this->cdn->getUrl($name)),
                new Size(strlen($content))
            );

            $img = $composition->image;
            $imgFile = null;
            if ($img) {
                $width = imagesx($img);
                $height = imagesy($img);

                // Convert image to PNG
                ob_start();
                imagepng($img);
                $content = ob_get_contents(); // read from buffer
                ob_end_clean();

                // Save image to CDN
                $name = $this->cdn->generatePath('png', $ws, $user);
                $this->cdn->write($name, $content);

                $imgFile = new ImageFileEntity(
                    new Storage($this->cdn->getAdapterLookupKey()),
                    new ObjectKey($name),
                    new Url($this->cdn->getUrl($name)),
                    new Size(strlen($content)),
                    new Width($width),
                    new Height($height),
                    BlurhashGenerator::generateBlurHash($img, $width, $height),
                );
            }

            /** @var CreditCount */
            $cost = $composition->cost;

            $entity = new CompositionEntity(
                $ws,
                $user,
                $cmd->model,
                $composition->title ?? new Title(),
                $composition->details,
                $outputFile,
                $imgFile,
                RequestParams::fromArray($cmd->params),
                $cost
            );

            $this->repo->add($entity);

            // Deduct credit from workspace
            $ws->deductCredit($cost, $this->negativeBalance);

            // Dispatch event
            $event = new CreditUsageEvent($ws, $cost);
            $this->dispatcher->dispatch($event);

            $entities[] = $entity;
        }

        return $entities;
    }
}
