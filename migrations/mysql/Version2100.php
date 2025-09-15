<?php

declare(strict_types=1);

namespace Migrations\MySql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version2100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workspace ADD openai_api_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D94001915C81382 ON workspace (openai_api_key)');

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workspace ADD anthropic_api_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D940019F9E3111B ON workspace (anthropic_api_key)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D94001915C81382 ON workspace');
        $this->addSql('ALTER TABLE workspace DROP openai_api_key');

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D940019F9E3111B ON workspace');
        $this->addSql('ALTER TABLE workspace DROP anthropic_api_key');
    }
}
