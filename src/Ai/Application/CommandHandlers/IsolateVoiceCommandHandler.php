<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\IsolateVoiceCommand;
use Ai\Domain\Entities\IsolatedVoiceEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\IsolatedVoice\VoiceIsolatorServiceInterface;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Title;
use Billing\Domain\Events\CreditUsageEvent;
use Billing\Domain\ValueObjects\CreditCount;
use File\Domain\Entities\FileEntity;
use File\Domain\ValueObjects\ObjectKey;
use File\Domain\ValueObjects\Size;
use File\Domain\ValueObjects\Storage;
use File\Domain\ValueObjects\Url;
use Easy\Container\Attributes\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Shared\Infrastructure\FileSystem\CdnInterface;
use User\Domain\Entities\UserEntity;
use User\Domain\Repositories\UserRepositoryInterface;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class IsolateVoiceCommandHandler
{
    public function __construct(
        private AiServiceFactoryInterface $factory,
        private WorkspaceRepositoryInterface $wsRepo,
        private UserRepositoryInterface $userRepo,
        private LibraryItemRepositoryInterface $repo,
        private CdnInterface $cdn,
        private StreamFactoryInterface $streamFactory,
        private EventDispatcherInterface $dispatcher,

        #[Inject('option.billing.negative_balance_enabled')]
        private bool $negativeBalance = false,
    ) {}

    public function handle(IsolateVoiceCommand $cmd): IsolatedVoiceEntity
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

        $ext = pathinfo($cmd->file->getClientFilename(), PATHINFO_EXTENSION);

        // Save input file to CDN
        $stream = $cmd->file->getStream();
        $stream->rewind();
        $name = $this->cdn->generatePath($ext, $ws, $user);
        $this->cdn->write("/" . $name, $stream->getContents());

        $inputFile = new FileEntity(
            new Storage($this->cdn->getAdapterLookupKey()),
            new ObjectKey($name),
            new Url($this->cdn->getUrl($name)),
            new Size($cmd->file->getSize())
        );

        // Create service
        $service = $this->factory->create(
            VoiceIsolatorServiceInterface::class,
            $cmd->model
        );

        // Generate isolated voice
        // $stream->rewind();
        $resp = $service->generateIsolatedVoice(
            $cmd->model,
            $this->streamFactory->createStreamFromResource(
                $this->cdn->readStream($name)
            ),
            $cmd->params
        );

        // $resp->audioContent->rewind();
        $content = '';
        while (!$resp->audioContent->eof()) {
            $content .= $resp->audioContent->read(1);
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

        /** @var CreditCount */
        $cost = $resp->cost;

        $entity = new IsolatedVoiceEntity(
            $ws,
            $user,
            $cmd->model,
            $inputFile,
            $outputFile,
            new Title($cmd->file->getClientFilename()),
            RequestParams::fromArray($cmd->params),
            $cost
        );

        $this->repo->add($entity);

        // Deduct credit from workspace
        $ws->deductCredit($cost, $this->negativeBalance);

        // Dispatch event
        $event = new CreditUsageEvent($ws, $cost);
        $this->dispatcher->dispatch($event);

        return $entity;
    }
}
