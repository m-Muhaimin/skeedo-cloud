<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\FalAi;

use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\ValueObjects\State;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\Events\CreditUsageEvent;
use Billing\Domain\ValueObjects\CreditCount;
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
use stdClass;

class ImageWebhookProcessor
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
        ImageEntity $entity,
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
            && isset($data->payload->images)
        ) {
            $image = $data->payload->images[0];
            $type = isset($image->content_type) ? $image->content_type : 'image/png';
            $url = $image->url;

            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $resp = $this->client->sendRequest('GET', $url);
                $content = $resp->getBody()->getContents();
            } else {
                $data = str_replace('data:' . $type . ';base64,', '', $url);
                $content = base64_decode($data);
            }

            // Save image to CDN
            $name = $this->cdn->generatePath('png', $ws, $user);
            $this->cdn->write($name, $content);

            // Create image file entity
            $img = imagecreatefromstring($content);
            $width = imagesx($img);
            $height = imagesy($img);

            $file = new ImageFileEntity(
                new Storage($this->cdn->getAdapterLookupKey()),
                new ObjectKey($name),
                new Url($this->cdn->getUrl($name)),
                new Size(strlen($content)),
                new Width($width),
                new Height($height),
                BlurhashGenerator::generateBlurHash($img, $width, $height),
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
            $count = (int) $resp->getHeaderLine('x-fal-billable-units');

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
