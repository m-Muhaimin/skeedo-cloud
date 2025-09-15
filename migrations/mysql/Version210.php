<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stat (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', workspace_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', metric NUMERIC(23, 11) DEFAULT NULL, discr VARCHAR(255) NOT NULL, country_code VARCHAR(255) DEFAULT NULL, INDEX IDX_20B8FF2182D40A1F (workspace_id), INDEX IDX_20B8FF21AA9E377A (date), INDEX IDX_20B8FF21F026BB7C (country_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stat ADD CONSTRAINT FK_20B8FF2182D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939882D40A1F');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD country_code VARCHAR(255) DEFAULT NULL, ADD ip VARCHAR(255) DEFAULT NULL, ADD city_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE voice ADD supported_languages LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE sample_url sample_url LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace DROP FOREIGN KEY FK_8D9400199A1887DC');
        $this->addSql('ALTER TABLE workspace ADD CONSTRAINT FK_8D9400199A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stat DROP FOREIGN KEY FK_20B8FF2182D40A1F');
        $this->addSql('DROP TABLE stat');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939882D40A1F');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE user DROP country_code, DROP ip, DROP city_name');
        $this->addSql('ALTER TABLE voice DROP supported_languages, CHANGE sample_url sample_url LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE workspace DROP FOREIGN KEY FK_8D9400199A1887DC');
        $this->addSql('ALTER TABLE workspace ADD CONSTRAINT FK_8D9400199A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
