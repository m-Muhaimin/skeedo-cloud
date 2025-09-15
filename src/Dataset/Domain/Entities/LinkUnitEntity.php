<?php

declare(strict_types=1);

namespace Dataset\Domain\Entities;

use Ai\Domain\ValueObjects\Embedding;
use Dataset\Domain\ValueObjects\Title;
use Dataset\Domain\ValueObjects\Url;
use Doctrine\ORM\Mapping as ORM;
use Override;

#[ORM\Entity]
class LinkUnitEntity extends AbstractDataUnitEntity
{
    #[ORM\Embedded(class: Url::class, columnPrefix: false)]
    private Url $url;

    #[ORM\Embedded(class: Embedding::class, columnPrefix: false)]
    private Embedding $embedding;

    public function __construct(
        Url $url,
        Embedding $embedding,
    ) {
        parent::__construct();

        $this->url = $url;
        $this->embedding = $embedding;
        $this->setTitle($this->urlToTitle($url));
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    #[Override]
    public function getEmbedding(): Embedding
    {
        return $this->embedding;
    }

    private function urlToTitle(Url $url): Title
    {
        $url = $url->value;

        // Remove protocol (http://, https://) if present
        $url = preg_replace('/^(https?:\/\/)/', '', $url);

        // Split the URL by '/' and remove empty parts
        $parts = array_filter(explode('/', $url));

        $title = null;

        // If there's a filename at the end (e.g., 'about.html'), use it
        if (!empty($parts)) {
            $lastPart = end($parts);
            if (strpos($lastPart, '.') !== false) {
                $title = explode('.', $lastPart)[0];
            }
        }

        // If no filename, use the last meaningful part of the path
        if (!$title) {
            for ($i = count($parts) - 1; $i >= 0; $i--) {
                if ($parts[$i] !== 'index' && !preg_match('/^\d+$/', $parts[$i])) {
                    $title = $parts[$i];
                    break;
                }
            }
        }

        // If no meaningful path parts, use the domain name
        if (!$title) {
            $title = $parts[0] ?? '';
        }

        // Convert raw title to a more readable format
        $title = implode(' ', array_map(function ($word) {
            return ucfirst(strtolower($word));
        }, preg_split('/[-_]/', $title)));

        return new Title($title ?: null);
    }
}
