<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Luma;

use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Progress;
use Ai\Domain\ValueObjects\State;
use Ai\Infrastructure\Services\CostCalculator;
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

        // Update state
        $state = $data->state ?? null;
        match ($state) {
            'failed' => $entity->setState(State::FAILED),
            'queued' => $entity->setState(State::QUEUED),
            'dreaming' => $entity->setState(State::PROCESSING),
            'completed' => $entity->setState(State::COMPLETED)
        };

        if ($entity->getState() == State::FAILED) {
            if (isset($data->failure_reason) && $data->failure_reason !== null) {
                $entity->addMeta('failure_reason', $data->failure_reason);
            }

            $reserved = new CreditCount(
                (float) ($entity->getMeta('reserved_credit') ?: 0)
            );
            $ws->unallocate($reserved);

            return;
        }


        // Update progress
        $progress = null;
        if (
            $entity->getState() == State::PROCESSING
            && isset($data->assets->progress_video)
            && $data->assets->progress_video !== null
        ) {
            // Extract progress percentage from URL
            if (preg_match('/\/(\d+)\.mp4$/', $data->assets->progress_video, $matches)) {
                $progress = (int) $matches[1];
            }
        }

        $entity->setProgress(new Progress($progress));


        // Update cover image
        if (
            !$entity->getCoverImage()
            && isset($data->assets->image)
            && filter_var($data->assets->image, FILTER_VALIDATE_URL)
        ) {
            $resp = $this->client->sendRequest('GET', $data->assets->image);
            $content = $resp->getBody()->getContents();

            $img = imagecreatefromstring($content);
            $width = imagesx($img);
            $height = imagesy($img);

            $key = $this->cdn->generatePath('jpg', $ws, $user);
            $this->cdn->write($key, $content);

            $imgFile = new ImageFileEntity(
                new Storage($this->cdn->getAdapterLookupKey()),
                new ObjectKey($key),
                new Url($this->cdn->getUrl($key)),
                new Size(strlen($content)),
                new Width($width),
                new Height($height),
                BlurhashGenerator::generateBlurHash($img, $width, $height),
            );

            $entity->setCoverImage($imgFile);

            if (!$entity->hasMeta('luma_cost_calculated')) {
                $cost = $this->calculateCost(
                    $entity->getModel(),
                    $data,
                    $width,
                    $height
                );

                $entity->addCost($cost);
                $entity->addMeta('luma_cost_calculated', true);

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

        if (
            !$entity->getOutputFile()
            && isset($data->assets->video)
            && filter_var($data->assets->video, FILTER_VALIDATE_URL)
        ) {
            $resp = $this->client->sendRequest('GET', $data->assets->video);
            $content = $resp->getBody()->getContents();

            $ext = pathinfo($data->assets->video, PATHINFO_EXTENSION);
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
    }

    private function calculateCost(
        Model $model,
        stdClass $data,
        int $width,
        int $height
    ): CreditCount {
        $fps = 24;
        $duration = (int)($data->request->duration ?: 5);
        $ppf =  $width * $height;

        $total = $ppf * $duration * $fps + $ppf;

        return $this->calc->calculate(
            $total,
            $model
        );
    }
}
