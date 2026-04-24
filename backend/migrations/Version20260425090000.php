<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un index unique sur user_quests pour interdire les doublons (user, template, event).';
    }

    public function up(Schema $schema): void
    {
        // PostgreSQL 15+ : NULLS NOT DISTINCT permet de traiter NULL comme valeur unique
        // (cas des quêtes standard qui n'ont pas d'event).
        $this->addSql('CREATE UNIQUE INDEX uniq_user_quest_template_event ON user_quests (user_id, quest_template_id, event_id) NULLS NOT DISTINCT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_user_quest_template_event');
    }
}
