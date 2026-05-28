<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Réparation : Version20260528250000 peut être marquée exécutée sans avoir
 * appliqué les colonnes events (contenu de migration remplacé en cours de projet).
 */
final class Version20260528280000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix events — garantir xp_multiplier, gold_multiplier, bonus_rules.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS xp_multiplier DOUBLE PRECISION NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS gold_multiplier DOUBLE PRECISION NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS bonus_rules JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS bonus_rules');
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS gold_multiplier');
        $this->addSql('ALTER TABLE events DROP COLUMN IF EXISTS xp_multiplier');
    }
}
