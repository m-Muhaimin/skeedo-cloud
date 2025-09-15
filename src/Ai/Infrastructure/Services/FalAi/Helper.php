<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\FalAi;

use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Entities\VideoEntity;
use Easy\Container\Attributes\Inject;

class Helper
{
    public function __construct(
        #[Inject('option.site.domain')]
        private ?string $domain = null,

        #[Inject('option.site.is_secure')]
        private ?string $isSecure = null,
    ) {}

    public function getCallBackUrl(VideoEntity|ImageEntity $video): string
    {
        $protocol = $this->isSecure ? 'https' : 'http';
        $domain = $this->domain;

        return sprintf(
            '%s://%s/webhooks/falai/%s',
            $protocol,
            $domain,
            $video->getId()->getValue(),
        );
    }
}
