<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Migrations;

interface MigrationInterface
{
    public function up(): void;
}
