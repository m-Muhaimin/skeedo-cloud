<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version270 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE affiliate (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', payout_method VARCHAR(255) DEFAULT NULL, paypal_email VARCHAR(255) DEFAULT NULL, bank_requisites VARCHAR(255) DEFAULT NULL, code VARCHAR(255) NOT NULL, click_count INT NOT NULL, referral_count INT NOT NULL, balance_amount INT NOT NULL, pending_amount INT NOT NULL, withdrawn_amount INT NOT NULL, UNIQUE INDEX UNIQ_597AA5CF77153098 (code), UNIQUE INDEX UNIQ_597AA5CFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payout (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', affiliate_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, amount INT NOT NULL, INDEX IDX_4E2EA9029F12C49A (affiliate_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE affiliate ADD CONSTRAINT FK_597AA5CFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE payout ADD CONSTRAINT FK_4E2EA9029F12C49A FOREIGN KEY (affiliate_id) REFERENCES affiliate (id)');
        $this->addSql('ALTER TABLE user ADD referred_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6498C0C9F8A FOREIGN KEY (referred_by) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D93D6498C0C9F8A ON user (referred_by)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE affiliate DROP FOREIGN KEY FK_597AA5CFA76ED395');
        $this->addSql('ALTER TABLE payout DROP FOREIGN KEY FK_4E2EA9029F12C49A');
        $this->addSql('DROP TABLE affiliate');
        $this->addSql('DROP TABLE payout');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6498C0C9F8A');
        $this->addSql('DROP INDEX IDX_8D93D6498C0C9F8A ON user');
        $this->addSql('ALTER TABLE user DROP referred_by');
    }
}
