<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use JsonSerializable;
use Plugin\Domain\Context;
use Presentation\Resources\DateTimeResource;

class PluginResource implements JsonSerializable
{
    public function __construct(
        private Context $context
    ) {}

    /** @return array  */
    public function jsonSerialize(): array
    {
        $c = $this->context;

        return [
            'type' => $c->type,
            'name' => $c->name,
            'description' => $c->description,
            'version' => $c->version,
            'homepage' => $c->homepage,
            'released_at' => new DateTimeResource($c->releasedAt),
            'tagline' => $c->tagline,
            'title' => $c->title,
            'logo' => $c->logo,
            'support_channels' => $c->supportChannels,
            'licenses' => $c->licenses,
            'authors' => $c->authors,
            'default_url' => $c->defaultUrl,
            'status' => $c->getStatus(),
        ];
    }
}
