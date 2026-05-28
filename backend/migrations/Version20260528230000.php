<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Post-MVP PMVP-023 — progression JSON sur user_quests.
 */
final class Version20260528230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Post-MVP PMVP-023 : colonne user_quests.progress (jsonb, défaut {}).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user_quests ADD progress JSONB NOT NULL DEFAULT '{}'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_quests DROP progress');
    }
}
