<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version290 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assistant_data_unit (assistant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', data_unit_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', INDEX IDX_E7619362E05387EF (assistant_id), INDEX IDX_E76193629CB626FC (data_unit_id), PRIMARY KEY(assistant_id, data_unit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE data_unit (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', file_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', title VARCHAR(255) DEFAULT NULL, discr VARCHAR(255) NOT NULL, url LONGTEXT DEFAULT NULL, embedding JSON DEFAULT NULL, INDEX IDX_67F4C7B193CB796C (file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE assistant_data_unit ADD CONSTRAINT FK_E7619362E05387EF FOREIGN KEY (assistant_id) REFERENCES assistant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE assistant_data_unit ADD CONSTRAINT FK_E76193629CB626FC FOREIGN KEY (data_unit_id) REFERENCES data_unit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE data_unit ADD CONSTRAINT FK_67F4C7B193CB796C FOREIGN KEY (file_id) REFERENCES file (id)');
        $this->addSql('ALTER TABLE plan ADD member_cap INT DEFAULT NULL');
        $this->addSql('ALTER TABLE plan_snapshot ADD member_cap INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD workspace_cap INT DEFAULT NULL');

        //! Update existing rows to set workspace_cap to 0
        //! 0 means inherit from the global settings
        $this->addSql('UPDATE user SET workspace_cap = 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assistant_data_unit DROP FOREIGN KEY FK_E7619362E05387EF');
        $this->addSql('ALTER TABLE assistant_data_unit DROP FOREIGN KEY FK_E76193629CB626FC');
        $this->addSql('ALTER TABLE data_unit DROP FOREIGN KEY FK_67F4C7B193CB796C');
        $this->addSql('DROP TABLE assistant_data_unit');
        $this->addSql('DROP TABLE data_unit');
        $this->addSql('ALTER TABLE plan DROP member_cap');
        $this->addSql('ALTER TABLE plan_snapshot DROP member_cap');
        $this->addSql('ALTER TABLE user DROP workspace_cap');
    }
}
