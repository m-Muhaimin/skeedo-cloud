<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assistant (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', status SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', name VARCHAR(64) NOT NULL, expertise VARCHAR(128) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, instructions LONGTEXT DEFAULT NULL, avatar_url LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', conversation_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', parent_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', assistant_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', file_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', role VARCHAR(24) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', model VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, quote LONGTEXT DEFAULT NULL, used_credit_count NUMERIC(23, 11) DEFAULT NULL, INDEX IDX_B6BD307F9AC0396 (conversation_id), INDEX IDX_B6BD307F727ACA70 (parent_id), INDEX IDX_B6BD307FE05387EF (assistant_id), INDEX IDX_B6BD307FA76ED395 (user_id), INDEX IDX_B6BD307F93CB796C (file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES library_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F727ACA70 FOREIGN KEY (parent_id) REFERENCES message (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE05387EF FOREIGN KEY (assistant_id) REFERENCES assistant (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F93CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE library_item CHANGE model model VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE plan_snapshot ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE voice CHANGE model model VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace ADD address JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F727ACA70');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FE05387EF');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA76ED395');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F93CB796C');
        $this->addSql('DROP TABLE assistant');
        $this->addSql('DROP TABLE message');
        $this->addSql('ALTER TABLE library_item CHANGE model model VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE plan_snapshot DROP updated_at');
        $this->addSql('ALTER TABLE voice CHANGE model model VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE workspace DROP address');
    }
}
