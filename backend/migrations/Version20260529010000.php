<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * V2.1 — Slots d'équipement bonus sur les items (`bonus_equip_slots`).
 */
final class Version20260529010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'V2.1 : colonne bonus_equip_slots sur items.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('items')) {
            return;
        }

        $table = $schema->getTable('items');
        if ($table->hasColumn('bonus_equip_slots')) {
            return;
        }

        $this->addSql('ALTER TABLE items ADD bonus_equip_slots INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('items') && $schema->getTable('items')->hasColumn('bonus_equip_slots')) {
            $this->addSql('ALTER TABLE items DROP COLUMN bonus_equip_slots');
        }
    }
}
