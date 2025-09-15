<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version260 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE message_library_item (message_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', library_item_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', INDEX IDX_7DD5DA95537A1329 (message_id), INDEX IDX_7DD5DA9568AEEA6E (library_item_id), PRIMARY KEY(message_id, library_item_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE message_library_item ADD CONSTRAINT FK_7DD5DA95537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_library_item ADD CONSTRAINT FK_7DD5DA9568AEEA6E FOREIGN KEY (library_item_id) REFERENCES library_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE file ADD embedding JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_library_item DROP FOREIGN KEY FK_7DD5DA95537A1329');
        $this->addSql('ALTER TABLE message_library_item DROP FOREIGN KEY FK_7DD5DA9568AEEA6E');
        $this->addSql('DROP TABLE message_library_item');
        $this->addSql('ALTER TABLE file DROP embedding');
    }
}
