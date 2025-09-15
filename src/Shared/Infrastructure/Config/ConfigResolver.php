<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Config;

use Easy\Container\Exceptions\NotFoundException;
use Easy\Container\ResolverInterface;
use Override;
use Throwable;

class ConfigResolver implements ResolverInterface
{
    public function __construct(private Config $config) {}

    #[Override]
    public function resolve(string $id): mixed
    {
        if (str_starts_with($id, 'config.')) {
            try {
                return $this->config->get(
                    str_replace('config.', '', $id)
                );
            } catch (Throwable $th) {
                if (!$this->canResolve($id)) {
                    throw new NotFoundException($id, 0, $th);
                }

                throw $th;
            }
        }

        throw new NotFoundException($id);
    }

    #[Override]
    public function canResolve(string $id): bool
    {
        if (str_starts_with($id, 'config.')) {
            try {
                $id = str_replace('config.', '', $id);
                return $this->config->has($id);
            } catch (Throwable $th) {
                return false;
            }
        }

        return false;
    }
}
