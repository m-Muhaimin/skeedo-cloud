<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version2130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` ADD status VARCHAR(20) NOT NULL');

        $this->addSql('UPDATE `order` SET status = "completed" WHERE is_fulfilled = 1 and is_paid = 1');
        $this->addSql('UPDATE `order` SET status = "processing" WHERE is_paid = 1 and is_fulfilled = 0');
        $this->addSql('UPDATE `order` SET status = "failed" WHERE is_fulfilled != 1 and is_paid != 1');

        $this->addSql('ALTER TABLE `order` DROP is_paid, DROP is_fulfilled');
        $this->addSql('CREATE INDEX IDX_F52993987B00651C ON `order` (status)');

        $this->addSql('ALTER TABLE voice ADD workspace_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', ADD user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', ADD visibility SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE voice ADD CONSTRAINT FK_E7FB583B82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voice ADD CONSTRAINT FK_E7FB583BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E7FB583B82D40A1F ON voice (workspace_id)');
        $this->addSql('CREATE INDEX IDX_E7FB583BA76ED395 ON voice (user_id)');

        $this->addSql('UPDATE voice SET visibility = 2');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_F52993987B00651C ON `order`');
        $this->addSql('ALTER TABLE `order` ADD is_paid TINYINT(1) DEFAULT 0 NOT NULL, ADD is_fulfilled TINYINT(1) DEFAULT 0 NOT NULL');

        $this->addSql('UPDATE `order` SET is_fulfilled = 1, is_paid = 1 WHERE status = "completed"');
        $this->addSql('UPDATE `order` SET is_paid = 1, is_fulfilled = 0 WHERE status = "processing"');
        $this->addSql('UPDATE `order` SET is_fulfilled != 1, is_paid != 1 WHERE status = "failed"');

        $this->addSql('ALTER TABLE `order` DROP status');

        $this->addSql('ALTER TABLE voice DROP FOREIGN KEY FK_E7FB583B82D40A1F');
        $this->addSql('ALTER TABLE voice DROP FOREIGN KEY FK_E7FB583BA76ED395');
        $this->addSql('DROP INDEX IDX_E7FB583B82D40A1F ON voice');
        $this->addSql('DROP INDEX IDX_E7FB583BA76ED395 ON voice');
        $this->addSql('ALTER TABLE voice DROP workspace_id, DROP user_id, DROP visibility');
    }
}
