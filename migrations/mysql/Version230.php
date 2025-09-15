<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F727ACA70');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F727ACA70 FOREIGN KEY (parent_id) REFERENCES message (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE voice ADD tones LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', ADD use_cases LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', DROP tone, DROP use_case, CHANGE supported_languages supported_languages LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F727ACA70');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F727ACA70 FOREIGN KEY (parent_id) REFERENCES message (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE voice ADD tone VARCHAR(255) DEFAULT NULL, ADD use_case VARCHAR(255) DEFAULT NULL, DROP tones, DROP use_cases, CHANGE supported_languages supported_languages LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\'');
    }
}
