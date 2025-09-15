<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Aimlapi;

use Ai\Domain\ValueObjects\CompositionDetails;
use Ai\Domain\Composition\CompositionResponse;
use Ai\Domain\Composition\CompositionServiceInterface;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Title;
use Ai\Infrastructure\Services\CostCalculator;
use Override;
use Traversable;

class CompositionService implements CompositionServiceInterface
{
    private array $models = [
        'aimlapi/chirp-v3.5',
        'aimlapi/chirp-v3',
    ];

    public function __construct(
        private readonly Client $client,
        private readonly CostCalculator $calc
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

    #[Override]
    public function generateComposition(
        Model $model,
        array $params = []
    ): Traversable {
        $custom = true;

        if (isset($params['instrumental']) && (bool) $params['instrumental']) {
            $custom = false;
        } else if (isset($params['tags']) && trim($params['tags']) !== '') {
            $custom = true;
        }

        $data = [
            'mv' => str_replace('aimlapi/', '', $model->value),
        ];

        if (isset($params['prompt'])) {
            $data[$custom ? 'prompt' : 'gpt_description_prompt'] = $params['prompt'];
        }

        if (isset($params['instrumental'])) {
            $data['make_instrumental'] = (bool) $params['instrumental'];
        }

        if ($custom) {
            if (isset($params['tags']) && trim($params['tags']) !== '') {
                $data['tags'] = $params['tags'];
            }
        }

        $resp = $this->client->sendRequest('POST', '/v2/generate/audio/suno-ai/clip/', $data);
        $content = $resp->getBody()->getContents();
        $data = json_decode($content);

        if ($resp->getStatusCode() !== 201) {
            throw new ApiException('Failed to generate composition: ' . $data?->message ?? '');
        }

        $processing = [];
        foreach ($data->clip_ids as $id) {
            $processing[$id] = (object)[
                'id' => $id,
                'status' => 'submitted',
            ];
        }

        while (true) {
            foreach ($processing as $id => $gen) {
                $resp = $this->client->sendRequest('GET', '/v2/generate/audio/suno-ai/clip/?status=queued&clip_id=' . $gen->id);
                $content = json_decode($resp->getBody()->getContents());

                if ($resp->getStatusCode() !== 200) {
                    throw new ApiException('Failed to check composition status.' . $content?->message ?? '');
                }

                $processing[$id] = $content;
                $gen = $content;

                if (in_array($gen->status, ['complete', 'completed'])) {
                    $audioUrl = $gen->audio_url;

                    $res = $this->client->sendRequest('GET', $audioUrl);

                    if ($res->getStatusCode() !== 200) {
                        throw new ApiException('Failed to download composition.');
                    }

                    $image = null;
                    if ($gen->image_url) {
                        // Get image
                        $imgRes = $this->client->sendRequest('GET', $gen->image_url);

                        if ($imgRes->getStatusCode() !== 200) {
                            throw new ApiException('Failed to download composition image.');
                        }

                        $image = imagecreatefromstring($imgRes->getBody()->getContents());
                    }

                    $cost = $this->calc->calculate(
                        157500 / count($data->clip_ids), // Fixed cost according to https://docs.aimlapi.com/api-overview/audio-models-music-and-vocal/suno-ai-v2/costs
                        $model
                    );

                    $audioContent = $res->getBody();

                    $details =  new CompositionDetails(
                        isset($gen->metadata->prompt) && trim($gen->metadata->prompt) !== '' ? $gen->metadata->prompt : null,
                        isset($gen->metadata->tags) && trim($gen->metadata->tags) !== '' ? $gen->metadata->tags : null,
                    );

                    yield new CompositionResponse(
                        $audioContent,
                        $cost,
                        $image,
                        new Title($gen->title && trim($gen->title) !== '' ? $gen->title : null),
                        $details
                    );

                    unset($processing[$id]);
                } elseif ($gen->status === 'error') {
                    // Handle error state
                    unset($processing[$id]);
                }
            }

            if (empty($processing)) {
                break;
            }

            sleep(5);
        }
    }
}
