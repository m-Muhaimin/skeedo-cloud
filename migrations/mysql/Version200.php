<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', storage VARCHAR(255) NOT NULL, object_key VARCHAR(255) NOT NULL, url LONGTEXT NOT NULL, size INT NOT NULL, discr VARCHAR(255) NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, blur_hash VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE library_item (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', workspace_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', preset_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', output_file_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', input_file_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', voice_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', visibility SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', request_params JSON NOT NULL, model VARCHAR(255) NOT NULL, used_credit_count NUMERIC(23, 11) DEFAULT NULL, discr VARCHAR(255) NOT NULL, title VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, INDEX IDX_B9D4EF7382D40A1F (workspace_id), INDEX IDX_B9D4EF73A76ED395 (user_id), INDEX IDX_B9D4EF7380688E6F (preset_id), INDEX IDX_B9D4EF734AC9FE8A (output_file_id), INDEX IDX_B9D4EF7386DDE0AC (input_file_id), INDEX IDX_B9D4EF731672336E (voice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `order` (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', workspace_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', plan_snapshot_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', currency_code VARCHAR(3) NOT NULL, is_paid TINYINT(1) DEFAULT 0 NOT NULL, is_fulfilled TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, trial_period_days INT DEFAULT NULL, payment_gateway VARCHAR(255) DEFAULT NULL, external_id VARCHAR(255) DEFAULT NULL, INDEX IDX_F529939882D40A1F (workspace_id), INDEX IDX_F52993988F80E135 (plan_snapshot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plan_snapshot (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', plan_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', billing_cycle VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, config JSON DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, icon LONGTEXT DEFAULT NULL, feature_list LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', price INT NOT NULL, credit_count NUMERIC(23, 11) DEFAULT NULL, INDEX IDX_2228224FE899029B (plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE voice (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', status SMALLINT NOT NULL, gender VARCHAR(16) DEFAULT NULL, accent VARCHAR(64) DEFAULT NULL, age VARCHAR(16) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', provider VARCHAR(255) NOT NULL, model VARCHAR(255) NOT NULL, external_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, sample_url LONGTEXT NOT NULL, tone VARCHAR(255) DEFAULT NULL, use_case VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workspace (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', owner_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', subscription_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', is_trialed TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, credit_count NUMERIC(23, 11) DEFAULT NULL, INDEX IDX_8D9400197E3C61F9 (owner_id), UNIQUE INDEX UNIQ_8D9400199A1887DC (subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workspace_user (workspace_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', INDEX IDX_C971A58B82D40A1F (workspace_id), INDEX IDX_C971A58BA76ED395 (user_id), PRIMARY KEY(workspace_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workspace_invitation (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', workspace_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, email VARCHAR(255) NOT NULL, INDEX IDX_18AAE8AD82D40A1F (workspace_id), UNIQUE INDEX UNIQ_18AAE8ADE7927C7482D40A1F (email, workspace_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE library_item ADD CONSTRAINT FK_B9D4EF7382D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE library_item ADD CONSTRAINT FK_B9D4EF73A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE library_item ADD CONSTRAINT FK_B9D4EF7380688E6F FOREIGN KEY (preset_id) REFERENCES preset (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE library_item ADD CONSTRAINT FK_B9D4EF734AC9FE8A FOREIGN KEY (output_file_id) REFERENCES file (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE library_item ADD CONSTRAINT FK_B9D4EF7386DDE0AC FOREIGN KEY (input_file_id) REFERENCES file (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE library_item ADD CONSTRAINT FK_B9D4EF731672336E FOREIGN KEY (voice_id) REFERENCES voice (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993988F80E135 FOREIGN KEY (plan_snapshot_id) REFERENCES plan_snapshot (id)');
        $this->addSql('ALTER TABLE plan_snapshot ADD CONSTRAINT FK_2228224FE899029B FOREIGN KEY (plan_id) REFERENCES plan (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE workspace ADD CONSTRAINT FK_8D9400197E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workspace ADD CONSTRAINT FK_8D9400199A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)');
        $this->addSql('ALTER TABLE workspace_user ADD CONSTRAINT FK_C971A58B82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id)');
        $this->addSql('ALTER TABLE workspace_user ADD CONSTRAINT FK_C971A58BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workspace_invitation ADD CONSTRAINT FK_18AAE8AD82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id)');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A7680688E6F');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76A76ED395');
        $this->addSql('DROP TABLE document');
        $this->addSql('ALTER TABLE plan ADD snapshot_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', ADD config JSON DEFAULT NULL, ADD credit_count NUMERIC(23, 11) DEFAULT NULL, DROP token_credit_count, DROP image_credit_count, DROP audio_credit_count');
        $this->addSql('ALTER TABLE plan ADD CONSTRAINT FK_DD5A5B7D7B39395E FOREIGN KEY (snapshot_id) REFERENCES plan_snapshot (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DD5A5B7D7B39395E ON plan (snapshot_id)');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('DROP INDEX IDX_A3C664D3A76ED395 ON subscription');
        $this->addSql('ALTER TABLE subscription ADD order_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', ADD canceled_at DATETIME DEFAULT NULL, ADD cancel_at DATETIME DEFAULT NULL, ADD ended_at DATETIME DEFAULT NULL, ADD renew_at DATETIME DEFAULT NULL, ADD usage_count NUMERIC(23, 11) DEFAULT NULL, DROP status, DROP reset_credits_at, DROP token_usage_count, DROP image_usage_count, DROP audio_usage_count, DROP customer_external_id, DROP price_external_id, DROP product_external_id, DROP currency, DROP expire_at, CHANGE user_id workspace_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D38D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D382D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan_snapshot (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A3C664D38D9F6D38 ON subscription (order_id)');
        $this->addSql('CREATE INDEX IDX_A3C664D382D40A1F ON subscription (workspace_id)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6499A208144');
        $this->addSql('DROP INDEX UNIQ_8D93D6499A208144 ON user');
        $this->addSql('ALTER TABLE user CHANGE active_subscription_id current_workspace_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6497D65B4C4 FOREIGN KEY (current_workspace_id) REFERENCES workspace (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D93D6497D65B4C4 ON user (current_workspace_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D38D9F6D38');
        $this->addSql('ALTER TABLE plan DROP FOREIGN KEY FK_DD5A5B7D7B39395E');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D382D40A1F');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6497D65B4C4');
        $this->addSql('CREATE TABLE document (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', preset_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, title VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, output LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, INDEX IDX_D8698A76A76ED395 (user_id), INDEX IDX_D8698A7680688E6F (preset_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A7680688E6F FOREIGN KEY (preset_id) REFERENCES preset (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE library_item DROP FOREIGN KEY FK_B9D4EF7382D40A1F');
        $this->addSql('ALTER TABLE library_item DROP FOREIGN KEY FK_B9D4EF73A76ED395');
        $this->addSql('ALTER TABLE library_item DROP FOREIGN KEY FK_B9D4EF7380688E6F');
        $this->addSql('ALTER TABLE library_item DROP FOREIGN KEY FK_B9D4EF734AC9FE8A');
        $this->addSql('ALTER TABLE library_item DROP FOREIGN KEY FK_B9D4EF7386DDE0AC');
        $this->addSql('ALTER TABLE library_item DROP FOREIGN KEY FK_B9D4EF731672336E');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939882D40A1F');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993988F80E135');
        $this->addSql('ALTER TABLE plan_snapshot DROP FOREIGN KEY FK_2228224FE899029B');
        $this->addSql('ALTER TABLE workspace DROP FOREIGN KEY FK_8D9400197E3C61F9');
        $this->addSql('ALTER TABLE workspace DROP FOREIGN KEY FK_8D9400199A1887DC');
        $this->addSql('ALTER TABLE workspace_user DROP FOREIGN KEY FK_C971A58B82D40A1F');
        $this->addSql('ALTER TABLE workspace_user DROP FOREIGN KEY FK_C971A58BA76ED395');
        $this->addSql('ALTER TABLE workspace_invitation DROP FOREIGN KEY FK_18AAE8AD82D40A1F');
        $this->addSql('DROP TABLE file');
        $this->addSql('DROP TABLE library_item');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE plan_snapshot');
        $this->addSql('DROP TABLE voice');
        $this->addSql('DROP TABLE workspace');
        $this->addSql('DROP TABLE workspace_user');
        $this->addSql('DROP TABLE workspace_invitation');
        $this->addSql('DROP INDEX IDX_8D93D6497D65B4C4 ON user');
        $this->addSql('ALTER TABLE user CHANGE current_workspace_id active_subscription_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6499A208144 FOREIGN KEY (active_subscription_id) REFERENCES subscription (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6499A208144 ON user (active_subscription_id)');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('DROP INDEX UNIQ_A3C664D38D9F6D38 ON subscription');
        $this->addSql('DROP INDEX IDX_A3C664D382D40A1F ON subscription');
        $this->addSql('ALTER TABLE subscription ADD status INT NOT NULL, ADD reset_credits_at DATETIME DEFAULT NULL, ADD token_usage_count INT DEFAULT NULL, ADD image_usage_count INT DEFAULT NULL, ADD audio_usage_count INT DEFAULT NULL, ADD customer_external_id VARCHAR(255) DEFAULT NULL, ADD price_external_id VARCHAR(255) DEFAULT NULL, ADD product_external_id VARCHAR(255) DEFAULT NULL, ADD currency VARCHAR(3) NOT NULL, ADD expire_at DATETIME DEFAULT NULL, DROP order_id, DROP canceled_at, DROP cancel_at, DROP ended_at, DROP renew_at, DROP usage_count, CHANGE workspace_id user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_A3C664D3A76ED395 ON subscription (user_id)');
        $this->addSql('DROP INDEX UNIQ_DD5A5B7D7B39395E ON plan');
        $this->addSql('ALTER TABLE plan ADD token_credit_count INT DEFAULT NULL, ADD image_credit_count INT DEFAULT NULL, ADD audio_credit_count INT DEFAULT NULL, DROP snapshot_id, DROP config, DROP credit_count');
    }
}
