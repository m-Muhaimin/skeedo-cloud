<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\FalAi;

use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\ValueObjects\State;
use Ai\Infrastructure\Services\CostCalculator;
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
use stdClass;

class VideoWebhookProcessor
{
    public function __construct(
        private Client $client,
        private CdnInterface $cdn,
        private CostCalculator $calc,
        private EventDispatcherInterface $dispatcher,

        #[Inject('option.billing.negative_balance_enabled')]
        private bool $negativeBalance = false,
    ) {}

    public function __invoke(
        VideoEntity $entity,
        stdClass $data
    ): void {
        $user = $entity->getUser();
        $ws = $entity->getWorkspace();

        // Update status
        $state = $data->status ?? null;
        match ($state) {
            'IN_QUEUE' => $entity->setState(State::QUEUED),
            'IN_PROGRESS' => $entity->setState(State::PROCESSING),
            'COMPLETED' => $entity->setState(State::COMPLETED),
            'OK' => $entity->setState(State::COMPLETED),
            'ERROR' => $entity->setState(State::FAILED),
        };

        if ($entity->getState() == State::FAILED) {
            $entity->addMeta(
                'failure_reason',
                $data->payload->detail[0]->msg ?? $data->error ?? 'Unknown error'
            );

            $reserved = new CreditCount(
                (float) ($entity->getMeta('reserved_credit') ?: 0)
            );
            $ws->unallocate($reserved);

            return;
        }

        if (
            $entity->getState() == State::COMPLETED
            && !$entity->getOutputFile()
            && isset($data->payload->video->url)
            && filter_var($data->payload->video->url, FILTER_VALIDATE_URL)
        ) {
            $resp = $this->client->sendRequest('GET', $data->payload->video->url);
            $content = $resp->getBody()->getContents();

            $ext = pathinfo($data->payload->video->url, PATHINFO_EXTENSION);
            $key = $this->cdn->generatePath($ext, $ws, $user);
            $this->cdn->write($key, $content);

            $file = new FileEntity(
                new Storage($this->cdn->getAdapterLookupKey()),
                new ObjectKey($key),
                new Url($this->cdn->getUrl($key)),
                new Size(strlen($content)),
            );

            $entity->setOutputFile($file);
        }

        if (
            $entity->getState() == State::COMPLETED
            && $entity->hasMeta('falai_response_url')
            && !$entity->hasMeta('falai_cost_calculated')
        ) {
            $url = $entity->getMeta('falai_response_url');
            $resp = $this->client->sendRequest('GET', $url);

            $count = $resp->hasHeader('x-fal-billable-units')
                ? (int) $resp->getHeaderLine('x-fal-billable-units')
                : 1;

            $cost = $this->calc->calculate(
                $count,
                $entity->getModel()
            );

            $entity->addCost($cost);
            $entity->addMeta('falai_cost_calculated', true);

            $reserved = new CreditCount(
                (float) ($entity->getMeta('reserved_credit') ?: 0)
            );
            $ws->unallocate($reserved);

            // Deduct credit from workspace
            $ws->deductCredit($cost, $this->negativeBalance);

            // Dispatch event
            $event = new CreditUsageEvent($ws, $cost);
            $this->dispatcher->dispatch($event);
        }
    }
}
