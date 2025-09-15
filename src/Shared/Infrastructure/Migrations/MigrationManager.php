<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Migrations;

use Easy\Container\Attributes\Inject;
use Option\Application\Commands\SaveOptionCommand;
use Psr\Container\ContainerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Throwable;

class MigrationManager
{
    public function __construct(
        private ContainerInterface $container,
        private Dispatcher $dispatcher,

        #[Inject('config.dirs.root')]
        private string $rootDir,

        #[Inject('option.migrated')]
        private array $migrated = [],
    ) {}

    public function run(): void
    {
        try {
            $this->doRun();
        } catch (Throwable) {
        }
    }

    public function doRun(): void
    {
        $migrationDir = $this->rootDir . '/migrations/update';
        $files = glob($migrationDir . '/*.php');
        $migrationClasses = [];

        foreach ($files as $file) {
            // Extract class name from file name
            $className = pathinfo($file, PATHINFO_FILENAME);

            // Assume namespace is \Migrations\Update\ClassName
            $fqcn = "Migrations\\Update\\$className";

            if (class_exists($fqcn) && in_array(MigrationInterface::class, class_implements($fqcn))) {
                $migrationClasses[] = $fqcn;
            }
        }

        // Sort alphabetically
        sort($migrationClasses);

        // Filter out already migrated
        $pendingMigrations = array_diff($migrationClasses, $this->migrated);

        foreach ($pendingMigrations as $migrationClass) {
            /** @var MigrationInterface $migration */
            $migration = $this->container->get($migrationClass);
            $migration->up();

            $this->migrated[] = $migrationClass;
            $cmd = new SaveOptionCommand('migrated', json_encode($this->migrated));
            $this->dispatcher->dispatch($cmd);
        }
    }
}
