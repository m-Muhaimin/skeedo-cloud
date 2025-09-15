<?php

declare(strict_types=1);

namespace Plugin\Infrastructure;

use Easy\Container\Attributes\Inject;
use Plugin\Domain\Context;
use Plugin\Domain\Exceptions\PluginInterfaceNotImplementedException;
use Plugin\Domain\PluginInterface;
use Plugin\Domain\PluginWrapper;
use Plugin\Domain\Repositories\PluginRepositoryInterface;
use Plugin\Domain\ValueObjects\Status;
use Plugin\Domain\ValueObjects\Type;
use Psr\Container\ContainerInterface;

class PluginLoader
{
    public function __construct(
        private ContainerInterface $container,
        private PluginRepositoryInterface $repo,

        #[Inject('option.theme')]
        private string $theme = 'heyaikeedo/default',
    ) {
    }

    public function load(string $path): PluginWrapper
    {
        $context = new Context($path);

        $plugin = null;

        if ($context->entryClass->value) {
            $plugin = $this->container->get($context->entryClass->value);
        }

        if ($plugin && !($plugin instanceof PluginInterface)) {
            throw new PluginInterfaceNotImplementedException();
        }

        $pw = new PluginWrapper($context, $plugin);
        $this->repo->add($pw);

        if ($context->type == Type::THEME) {
            $context->setStatus(
                $context->name->value === $this->theme
                    ? Status::ACTIVE
                    : Status::INACTIVE
            );
        }

        if ($plugin && $context->getStatus() == Status::ACTIVE) {
            $pw->plugin->boot($context);
        }

        return $pw;
    }
}
