<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422095500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add xp_reward to events for end-of-event rewards.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events ADD xp_reward INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events DROP xp_reward');
    }
}
