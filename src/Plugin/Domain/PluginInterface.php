<?php

namespace Plugin\Domain;

interface PluginInterface
{
    /**
     * Boots the plugin. This is called when application boots but before
     * handling the http server request.
     *
     * @param Context $context
     * @return void
     */
    public function boot(Context $context): void;
}
