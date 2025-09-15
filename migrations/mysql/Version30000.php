<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version30000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE affiliate CHANGE id id BINARY(16) NOT NULL, CHANGE user_id user_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE assistant ADD model VARCHAR(255) DEFAULT NULL, ADD position NUMERIC(21, 20) NOT NULL, CHANGE id id BINARY(16) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE assistant_data_unit CHANGE assistant_id assistant_id BINARY(16) NOT NULL, CHANGE data_unit_id data_unit_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE category CHANGE id id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE coupon CHANGE id id BINARY(16) NOT NULL, CHANGE plan_id plan_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE data_unit CHANGE id id BINARY(16) NOT NULL, CHANGE file_id file_id BINARY(16) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE file CHANGE id id BINARY(16) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE library_item ADD state SMALLINT NOT NULL, ADD progress SMALLINT DEFAULT NULL, ADD meta JSON DEFAULT NULL, CHANGE id id BINARY(16) NOT NULL, CHANGE workspace_id workspace_id BINARY(16) NOT NULL, CHANGE user_id user_id BINARY(16) NOT NULL, CHANGE preset_id preset_id BINARY(16) DEFAULT NULL, CHANGE output_file_id output_file_id BINARY(16) DEFAULT NULL, CHANGE input_file_id input_file_id BINARY(16) DEFAULT NULL, CHANGE voice_id voice_id BINARY(16) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE cover_image_file_id cover_image_file_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE message CHANGE id id BINARY(16) NOT NULL, CHANGE conversation_id conversation_id BINARY(16) NOT NULL, CHANGE parent_id parent_id BINARY(16) DEFAULT NULL, CHANGE assistant_id assistant_id BINARY(16) DEFAULT NULL, CHANGE user_id user_id BINARY(16) DEFAULT NULL, CHANGE file_id file_id BINARY(16) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE message_library_item CHANGE message_id message_id BINARY(16) NOT NULL, CHANGE library_item_id library_item_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE `option` CHANGE id id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE `order` CHANGE id id BINARY(16) NOT NULL, CHANGE workspace_id workspace_id BINARY(16) NOT NULL, CHANGE plan_snapshot_id plan_snapshot_id BINARY(16) NOT NULL, CHANGE coupon_id coupon_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE payout CHANGE id id BINARY(16) NOT NULL, CHANGE affiliate_id affiliate_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE plan CHANGE id id BINARY(16) NOT NULL, CHANGE feature_list feature_list LONGTEXT DEFAULT NULL, CHANGE snapshot_id snapshot_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE plan_snapshot CHANGE id id BINARY(16) NOT NULL, CHANGE plan_id plan_id BINARY(16) DEFAULT NULL, CHANGE feature_list feature_list LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE preset ADD position NUMERIC(21, 20) NOT NULL, CHANGE id id BINARY(16) NOT NULL, CHANGE category_id category_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE stat CHANGE id id BINARY(16) NOT NULL, CHANGE workspace_id workspace_id BINARY(16) DEFAULT NULL, CHANGE date date DATE NOT NULL');
        $this->addSql('ALTER TABLE subscription CHANGE id id BINARY(16) NOT NULL, CHANGE workspace_id workspace_id BINARY(16) NOT NULL, CHANGE plan_id plan_id BINARY(16) NOT NULL, CHANGE order_id order_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD phone_number VARCHAR(30) DEFAULT NULL, CHANGE id id BINARY(16) NOT NULL, CHANGE current_workspace_id current_workspace_id BINARY(16) DEFAULT NULL, CHANGE referred_by referred_by BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE voice ADD position NUMERIC(21, 20) NOT NULL, CHANGE id id BINARY(16) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE supported_languages supported_languages LONGTEXT DEFAULT NULL, CHANGE tones tones LONGTEXT DEFAULT NULL, CHANGE use_cases use_cases LONGTEXT DEFAULT NULL, CHANGE workspace_id workspace_id BINARY(16) DEFAULT NULL, CHANGE user_id user_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace CHANGE id id BINARY(16) NOT NULL, CHANGE owner_id owner_id BINARY(16) DEFAULT NULL, CHANGE subscription_id subscription_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace_user CHANGE workspace_id workspace_id BINARY(16) NOT NULL, CHANGE user_id user_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE workspace_invitation CHANGE id id BINARY(16) NOT NULL, CHANGE workspace_id workspace_id BINARY(16) DEFAULT NULL');

        // Set the position for the first preset to 1
        $this->addSql('SET @position := 1;');
        $this->addSql('UPDATE preset SET position = (@position := @position * 0.998) ORDER BY id;');

        // Set the position for the first assistant to 1
        $this->addSql('SET @position := 1;');
        $this->addSql('UPDATE assistant SET position = (@position := @position * 0.998) ORDER BY id;');

        // Set the position for the first voice to 1
        $this->addSql('SET @position := 1;');
        $this->addSql('UPDATE voice SET position = (@position := @position * 0.998) ORDER BY id;');

        // Set all library items to completed
        $this->addSql('UPDATE library_item SET state = 3');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE affiliate CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE user_id user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE assistant DROP model, DROP position, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE assistant_data_unit CHANGE assistant_id assistant_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE data_unit_id data_unit_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE category CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE coupon CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE plan_id plan_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE data_unit CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE file_id file_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE file CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE library_item DROP state, DROP progress, DROP meta, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE workspace_id workspace_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE user_id user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE preset_id preset_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE output_file_id output_file_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE input_file_id input_file_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE voice_id voice_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE cover_image_file_id cover_image_file_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE message CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE conversation_id conversation_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE parent_id parent_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE assistant_id assistant_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE user_id user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE file_id file_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE message_library_item CHANGE message_id message_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE library_item_id library_item_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE `option` CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE `order` CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE workspace_id workspace_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE plan_snapshot_id plan_snapshot_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE coupon_id coupon_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE payout CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE affiliate_id affiliate_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE plan CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE feature_list feature_list LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE snapshot_id snapshot_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE plan_snapshot CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE feature_list feature_list LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE plan_id plan_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE preset DROP position, CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE category_id category_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE stat CHANGE date date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE workspace_id workspace_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE subscription CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE order_id order_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE plan_id plan_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE workspace_id workspace_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE user DROP phone_number, CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE current_workspace_id current_workspace_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE referred_by referred_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE voice DROP position, CHANGE tones tones LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE use_cases use_cases LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE supported_languages supported_languages LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE workspace_id workspace_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE user_id user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE workspace CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE owner_id owner_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE subscription_id subscription_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE workspace_invitation CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE workspace_id workspace_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE workspace_user CHANGE workspace_id workspace_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE user_id user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
    }
}
