<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version280 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE library_item ADD cover_image_file_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE library_item ADD CONSTRAINT FK_B9D4EF735514EC4C FOREIGN KEY (cover_image_file_id) REFERENCES file (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_B9D4EF735514EC4C ON library_item (cover_image_file_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE library_item DROP FOREIGN KEY FK_B9D4EF735514EC4C');
        $this->addSql('DROP INDEX IDX_B9D4EF735514EC4C ON library_item');
        $this->addSql('ALTER TABLE library_item DROP cover_image_file_id');
    }
}
