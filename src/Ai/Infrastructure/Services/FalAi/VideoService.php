<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\FalAi;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\Video\VideoServiceInterface;
use Ai\Infrastructure\Services\CostCalculator;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Visibility;
use Override;
use Psr\Http\Message\UploadedFileInterface;
use Shared\Infrastructure\FileSystem\CdnInterface;
use Shared\Infrastructure\Services\ModelRegistry;
use Throwable;
use Workspace\Domain\Entities\WorkspaceEntity;
use User\Domain\Entities\UserEntity;
use Traversable;

class VideoService implements VideoServiceInterface
{
    private ?array $models = null;

    public function __construct(
        private Client $client,
        private Helper $helper,
        private CostCalculator $calc,
        private ModelRegistry $registry,
        private CdnInterface $cdn,
        private EntityManagerInterface $em,
    ) {}

    #[Override]
    public function generateVideo(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?array $params = null
    ): VideoEntity {
        if (!$params || !array_key_exists('prompt', $params)) {
            throw new DomainException('Missing parameter: prompt');
        }

        $estimate = $this->calc->estimate($model);
        if (!$workspace->hasSufficientCredit($estimate)) {
            throw new InsufficientCreditsException();
        }

        $workspace->allocate($estimate);
        $this->em->flush(); // Save the workspace with the allocated credits

        $card = $this->models[$model->value];

        $endpoint = $card['config']['endpoint'] ?? $model->value;
        $entity = new VideoEntity(
            $workspace,
            $user,
            $model,
            RequestParams::fromArray($params)
        );

        $body = [
            "prompt" => $params['prompt']
        ];

        // negative prompt
        if (
            isset($params['negative_prompt'])
            && ($card['config']['negative_prompt'] ?? false)
        ) {
            $body['negative_prompt'] = $params['negative_prompt'];
        }

        foreach ($card['config']['params'] ?? [] as $p) {
            if (!isset($params[$p['key']])) {
                continue;
            }

            $allowed = array_map(fn($o) => $o['value'], $p['options'] ?? []);
            if (!in_array($params[$p['key']], $allowed)) {
                continue;
            }

            $val = $params[$p['key']];

            if ($val === 'true') {
                $val = true;
            } else if ($val === 'false') {
                $val = false;
            }

            $body[$p['key']] = $val;
        }

        if (isset($params['frames']) && isset($card['config']['frames'])) {
            $i = 0;
            $limit = $card['config']['frames']['limit'] ?? 1;

            /** @var UploadedFileInterface $frame */
            foreach ($params['frames'] as $frame) {
                $filename = $frame->getClientFilename();
                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                $key = $this->cdn->generatePath($extension, $workspace, $user);
                $this->cdn->write($key, $frame->getStream()->getContents(), [
                    // Always make it public even though the pre-signed secure 
                    // URLs option is enabled.
                    'visibility' => Visibility::PUBLIC
                ]);

                $url = $this->cdn->getUrl($key);

                if ($i == 0) {
                    $body['image_url'] = $url;
                } else if ($i + 1 == $limit) {
                    $body['tail_image_url'] = $url;
                }

                $i++;
                if ($i >= $limit) {
                    break;
                }
            }
        }

        if (
            isset($card['config']['frames']['endpoint'])
            && (isset($body['image_url']) || isset($body['tail_image_url']))
        ) {
            $endpoint = $card['config']['frames']['endpoint'];
        }

        try {
            $resp = $this->client->sendRequest(
                'POST',
                $endpoint,
                $body,
                ['fal_webhook' => $this->helper->getCallBackUrl($entity)]
            );
        } catch (Throwable $th) {
            $workspace->unallocate($estimate);
            throw $th;
        }

        $content = $resp->getBody()->getContents();
        $content = json_decode($content);

        $entity->addMeta('reserved_credit', $estimate->value);
        $entity->addMeta('falai_id', $content->request_id);
        $entity->addMeta('falai_response_url', $content->response_url);

        return $entity;
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        $this->parseDirectory();
        return array_key_exists($model->value, $this->models);
    }

    #[Override]
    public function getSupportedModels(): Traversable
    {
        $this->parseDirectory();

        foreach ($this->models as $key => $model) {
            yield new Model($key);
        }
    }

    private function parseDirectory(): void
    {
        if ($this->models !== null) {
            return;
        }

        $services = array_filter($this->registry['directory'], fn($service) => $service['key'] === 'falai');

        if (count($services) === 0) {
            $this->models = [];
            return;
        }

        $service = array_values($services)[0];
        $models = array_filter($service['models'], fn($model) => $model['type'] === 'video');

        $this->models = array_reduce($models, function ($carry, $model) {
            $carry[$model['key']] = $model;
            return $carry;
        }, []);
    }
}
