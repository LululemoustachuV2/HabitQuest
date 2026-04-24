<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add required_level to quest_templates.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quest_templates ADD required_level INT NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quest_templates DROP required_level');
    }
}
