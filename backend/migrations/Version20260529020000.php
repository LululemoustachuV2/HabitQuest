<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * V2.2 — baseDamage quêtes, bonus dégâts items, bossLevel monstres.
 */
final class Version20260529020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'V2.2 : quest_templates.base_damage, items bonus dégâts, monster_templates.boss_level.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE items ADD bonus_damage INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE items ADD bonus_damage_percent INT NOT NULL DEFAULT 0');

        $this->addSql('ALTER TABLE quest_templates ADD base_damage INT NOT NULL DEFAULT 15');
        $this->addSql("UPDATE quest_templates SET base_damage = 15 WHERE kind = 'daily'");
        $this->addSql("UPDATE quest_templates SET base_damage = 35 WHERE kind = 'weekly'");
        $this->addSql("UPDATE quest_templates SET base_damage = 25 WHERE kind = 'progression'");
        $this->addSql("UPDATE quest_templates SET base_damage = 20 WHERE kind = 'event'");

        $this->addSql('ALTER TABLE monster_templates ADD boss_level INT NOT NULL DEFAULT 1');
        $this->addSql('UPDATE monster_templates SET boss_level = level_min');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE items DROP bonus_damage');
        $this->addSql('ALTER TABLE items DROP bonus_damage_percent');
        $this->addSql('ALTER TABLE quest_templates DROP base_damage');
        $this->addSql('ALTER TABLE monster_templates DROP boss_level');
    }
}
