<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Luma;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\Video\VideoServiceInterface;
use Ai\Infrastructure\Services\CostCalculator;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Easy\Container\Attributes\Inject;
use League\Flysystem\Visibility;
use Override;
use Psr\Http\Message\UploadedFileInterface;
use Shared\Infrastructure\FileSystem\CdnInterface;
use Throwable;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class VideoService implements VideoServiceInterface
{
    private array $models = [
        'luma/ray-flash-2',
        'luma/ray-2',
        'luma/ray-1-6',
    ];

    public function __construct(
        private Client $client,
        private CostCalculator $calc,
        private CdnInterface $cdn,
        private EntityManagerInterface $em,

        #[Inject('option.site.domain')]
        private ?string $domain = null,

        #[Inject('option.site.is_secure')]
        private ?string $isSecure = null,
    ) {}

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }

    #[Override]
    public function getSupportedModels(): Traversable
    {
        foreach ($this->models as $model) {
            yield new Model($model);
        }
    }

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

        $entity = new VideoEntity(
            $workspace,
            $user,
            $model
        );

        $body = [
            'generation_type' => 'video',
            'prompt' => $params['prompt'],
            'aspect_ratio' => '16:9', // default
            'loop' => false, // default
            'callback_url' => $this->getCallBackUrl($entity),
            'model' => preg_replace('/^luma\//', '', $model->value),
            'resolution' => '1080p', // default
            'duration' => '5s', // default
        ];

        // aspect ratio
        $allowed = ['1:1', '16:9', '9:16', '4:3', '3:4', '21:9', '9:21'];
        if (
            isset($params['aspect_ratio'])
            && in_array($params['aspect_ratio'], $allowed)
        ) {
            $body['aspect_ratio'] = $params['aspect_ratio'];
        }

        // loop
        if (isset($params['loop'])) {
            $body['loop'] = (bool) $params['loop'];
        }

        // duration
        $allowed = [5, 9];
        if (
            isset($params['duration'])
            && in_array((int) $params['duration'], $allowed)
            && !in_array($model->value, ['luma/ray-1-6'])
        ) {
            $body['duration'] = ((int) $params['duration']) . 's';
        }

        // resolution
        $allowed = ['540p', '720p', '1080p', '4k'];
        if (
            isset($params['resolution'])
            && in_array($params['resolution'], $allowed)
        ) {
            $body['resolution'] = $params['resolution'];
        }

        if (in_array($model->value, ['luma/ray-1-6'])) {
            unset($body['duration']);
            unset($body['resolution']);
        }

        $frames = [];
        if (isset($params['frames'])) {
            $body['keyframes'] = [];
            $i = 0;

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

                $frame = [
                    "type" => "image",
                    "url" => $this->cdn->getUrl($key),
                ];

                $body['keyframes']["frame" . $i] = $frame;
                $frames[] = $frame['url'];

                $i++;

                if ($i > 1) {
                    break;
                }
            }
        }

        $params = $body;
        $params['frames'] = $frames;
        $entity->setRequestParams(RequestParams::fromArray($params));

        try {
            $resp = $this->client->sendRequest(
                'POST',
                '/dream-machine/v1/generations',
                $body
            );
        } catch (Throwable $th) {
            $workspace->unallocate($estimate);
            throw $th;
        }

        $content = $resp->getBody()->getContents();
        $content = json_decode($content);

        $entity->addMeta('reserved_credit', $estimate->value);
        $entity->addMeta('luma_id', $content->id);

        return $entity;
    }

    private function getCallBackUrl(VideoEntity $video): string
    {
        $protocol = $this->isSecure ? 'https' : 'http';
        $domain = $this->domain;

        return sprintf(
            '%s://%s/webhooks/luma/%s',
            $protocol,
            $domain,
            $video->getId()->getValue(),
        );
    }
}
