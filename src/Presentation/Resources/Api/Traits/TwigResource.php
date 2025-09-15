<?php

declare(strict_types=1);

namespace Presentation\Resources\Api\Traits;

trait TwigResource
{
    private ?array $data = null;

    public function __call($name, $arguments)
    {
        $data = $this->getData();
        return $data[$name] ?? null;
    }

    private function getData(): array
    {
        if ($this->data === null) {
            $this->data = json_decode(json_encode($this), true);
        }

        return $this->data;
    }
}
