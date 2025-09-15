<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\GenerateTranscriptionCommand;
use Ai\Domain\Entities\TranscriptionEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Title\TitleServiceInterface;
use Ai\Domain\Transcription\TranscriptionServiceInterface;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
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

class GenerateTranscriptionCommandHandler
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

    public function handle(GenerateTranscriptionCommand $cmd): TranscriptionEntity
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

        // Save file to CDN
        $stream = $cmd->file->getStream();
        $stream->rewind();
        $name = $this->cdn->generatePath($ext, $ws, $user);
        $this->cdn->write("/" . $name, $stream->getContents());

        $service = $this->factory->create(
            TranscriptionServiceInterface::class,
            $cmd->model
        );

        $stream->rewind();

        $resp = $service->generateTranscription(
            $cmd->model,
            $this->streamFactory->createStreamFromResource(
                $this->cdn->readStream($name, $stream->getContents())
            ),
            $cmd->params
        );

        $service = $this->factory->create(
            TitleServiceInterface::class,
            $ws->getSubscription()
                ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                : new Model('gpt-3.5-turbo')
        );

        $content = new Content($resp->transcription->text);
        $titleResp = $service->generateTitle(
            $content,
            $ws->getSubscription()
                ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                : new Model('gpt-3.5-turbo')
        );

        $file = new FileEntity(
            new Storage($this->cdn->getAdapterLookupKey()),
            new ObjectKey($name),
            new Url($this->cdn->getUrl($name)),
            new Size($cmd->file->getSize())
        );

        /** @var CreditCount */
        $cost = $resp->cost;
        $cost = new CreditCount($cost->value + $titleResp->cost->value);

        $entity = new TranscriptionEntity(
            $ws,
            $user,
            $file,
            $titleResp->title,
            $resp->transcription,
            $cmd->model,
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
