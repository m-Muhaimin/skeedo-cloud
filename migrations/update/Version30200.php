<?php

declare(strict_types=1);

namespace Migrations\Update;

use Easy\Container\Attributes\Inject;
use Shared\Infrastructure\Migrations\MigrationInterface;

class Version30200 implements MigrationInterface
{
    public function __construct(
        #[Inject('config.dirs.root')]
        private string $root
    ) {}

    public function up(): void
    {
        try {
            unlink($this->root . '/src/Presentation/RequestHandlers/App/Account/ProfileView.php');
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
