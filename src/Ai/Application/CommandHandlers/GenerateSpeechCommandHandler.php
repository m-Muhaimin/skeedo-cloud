<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\GenerateSpeechCommand;
use Ai\Domain\Entities\SpeechEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Speech\SpeechServiceInterface;
use Ai\Domain\Title\TitleServiceInterface;
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
use Shared\Infrastructure\FileSystem\CdnInterface;
use User\Domain\Entities\UserEntity;
use User\Domain\Repositories\UserRepositoryInterface;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\VoiceRepositoyInterface;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class GenerateSpeechCommandHandler
{
    public function __construct(
        private AiServiceFactoryInterface $factory,
        private WorkspaceRepositoryInterface $wsRepo,
        private UserRepositoryInterface $userRepo,
        private VoiceRepositoyInterface $voiceRepo,
        private LibraryItemRepositoryInterface $repo,
        private CdnInterface $cdn,
        private EventDispatcherInterface $dispatcher,

        #[Inject('option.billing.negative_balance_enabled')]
        private bool $negativeBalance = false,
    ) {}

    public function handle(GenerateSpeechCommand $cmd): SpeechEntity
    {
        $ws = $cmd->workspace instanceof WorkspaceEntity
            ? $cmd->workspace
            : $this->wsRepo->ofId($cmd->workspace);

        $user = $cmd->user instanceof UserEntity
            ? $cmd->user
            : $this->userRepo->ofId($cmd->user);

        $voice = $cmd->voice instanceof VoiceEntity
            ? $cmd->voice
            : $this->voiceRepo->ofId($cmd->voice);

        if (
            !is_null($ws->getTotalCreditCount()->value)
            && (float) $ws->getTotalCreditCount()->value <= 0
        ) {
            throw new InsufficientCreditsException();
        }

        $service = $this->factory->create(
            SpeechServiceInterface::class,
            $voice->getModel()
        );

        $resp = $service->generateSpeech(
            $voice,
            $cmd->params
        );

        $resp->audioContent->rewind();
        $content = $resp->audioContent->getContents();

        // Save image to CDN
        $name = $this->cdn->generatePath('mp3', $ws, $user);
        $this->cdn->write($name, $content);

        $file = new FileEntity(
            new Storage($this->cdn->getAdapterLookupKey()),
            new ObjectKey($name),
            new Url($this->cdn->getUrl($name)),
            new Size(strlen($content))
        );

        if (isset($cmd->params['prompt'])) {
            $service = $this->factory->create(
                TitleServiceInterface::class,
                $ws->getSubscription()
                    ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                    : new Model('gpt-3.5-turbo')
            );

            $titleResp = $service->generateTitle(
                new Content($cmd->params['prompt']),
                $ws->getSubscription()
                    ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                    : new Model('gpt-3.5-turbo')
            );

            $title = $titleResp->title;
            $titleCost = $titleResp->cost;
        } else {
            $title = 'Untitled Speech';
            $titleCost = new CreditCount(0);
        }

        /** @var CreditCount */
        $cost = $resp->cost;
        $cost = new CreditCount($cost->value + $titleCost->value);

        $entity = new SpeechEntity(
            $ws,
            $user,
            $file,
            $title,
            new Content($cmd->params['prompt'] ?? null),
            $voice,
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
