<?php

declare(strict_types=1);

namespace Plugin\Domain;

/**
 * Internal representation of the plugin instance
 */
class PluginWrapper
{
    public readonly ?PluginInterface $plugin;

    public function __construct(
        public readonly Context $context,
        ?PluginInterface $plugin = null,
    ) {
        $this->plugin = $plugin;
    }
}
