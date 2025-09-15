<?php

declare(strict_types=1);

use Affiliate\Infrastructure\AffiliateModuleBootstrapper;
use Ai\Infrastructure\AiModuleBootstrapper;
use Assistant\Infrastructure\AssistantModuleBootstrapper;
use Category\Infrastructure\CategoryModuleBootstrapper;
use Billing\Infrastructure\BillingModuleBootstrapper;
use File\Infrastructure\FileModuleBootstrapper;
use Option\Infrastructure\OptionModuleBootstrapper;
use Plugin\Infrastructure\PluginModuleBootstrapper;
use Preset\Infrastructure\PresetModuleBootstrapper;
use Shared\Infrastructure\Bootstrappers\ConsoleBootstrapper;
use Shared\Infrastructure\Bootstrappers\DoctrineBootstrapper;
use Shared\Infrastructure\Bootstrappers\FileSystemBootstrapper;
use Shared\Infrastructure\Bootstrappers\MailerBootstrapper;
use Shared\Infrastructure\Bootstrappers\PreferencesBootstrapper;
use Shared\Infrastructure\Bootstrappers\RoutingBootstrapper;
use Stat\Infrastructure\StatModuleBootstrapper;
use User\Infrastructure\UserModuleBootstrapper;
use Voice\Infrastructure\VoiceModuleBootstrapper;
use Workspace\Infrastructure\WorkspaceModuleBootstrapper;

return [
    FileSystemBootstrapper::class,
    DoctrineBootstrapper::class,

    OptionModuleBootstrapper::class,
    UserModuleBootstrapper::class,
    WorkspaceModuleBootstrapper::class,
    CategoryModuleBootstrapper::class,
    PresetModuleBootstrapper::class,
    BillingModuleBootstrapper::class,
    VoiceModuleBootstrapper::class,
    AiModuleBootstrapper::class,
    StatModuleBootstrapper::class,
    AssistantModuleBootstrapper::class,
    AffiliateModuleBootstrapper::class,
    FileModuleBootstrapper::class,

    ConsoleBootstrapper::class,
    RoutingBootstrapper::class,
    MailerBootstrapper::class,

    PluginModuleBootstrapper::class,
    PreferencesBootstrapper::class,
];
