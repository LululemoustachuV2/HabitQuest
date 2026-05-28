<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Post-MVP PMVP-025 — multiplicateurs et bonus_rules sur events.
 */
final class Version20260528250000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Post-MVP PMVP-025 : xp_multiplier, gold_multiplier, bonus_rules sur events.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS xp_multiplier DOUBLE PRECISION NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS gold_multiplier DOUBLE PRECISION NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE events ADD COLUMN IF NOT EXISTS bonus_rules JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events DROP bonus_rules');
        $this->addSql('ALTER TABLE events DROP gold_multiplier');
        $this->addSql('ALTER TABLE events DROP xp_multiplier');
    }
}
