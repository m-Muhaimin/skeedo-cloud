<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version2120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE coupon (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', plan_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', status SMALLINT NOT NULL, discount_type VARCHAR(255) NOT NULL, billing_cycle VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, starts_at DATETIME DEFAULT NULL, expires_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, title VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, cycle_count INT DEFAULT NULL, max_redemption_count INT DEFAULT NULL, amount INT NOT NULL, UNIQUE INDEX UNIQ_64BF3F0277153098 (code), INDEX IDX_64BF3F02E899029B (plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE coupon ADD CONSTRAINT FK_64BF3F02E899029B FOREIGN KEY (plan_id) REFERENCES plan (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `order` ADD coupon_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939866C5951B FOREIGN KEY (coupon_id) REFERENCES coupon (id)');
        $this->addSql('CREATE INDEX IDX_F529939866C5951B ON `order` (coupon_id)');
        $this->addSql('ALTER TABLE workspace ADD credits_adjusted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939866C5951B');
        $this->addSql('ALTER TABLE coupon DROP FOREIGN KEY FK_64BF3F02E899029B');
        $this->addSql('DROP TABLE coupon');
        $this->addSql('DROP INDEX IDX_F529939866C5951B ON `order`');
        $this->addSql('ALTER TABLE `order` DROP coupon_id');
        $this->addSql('ALTER TABLE workspace DROP credits_adjusted_at');
    }
}
