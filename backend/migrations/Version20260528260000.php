<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Post-MVP PMVP-035 — severity et is_fullscreen sur notifications.
 */
final class Version20260528260000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Post-MVP PMVP-035 : notifications.severity (info|warning|urgent) et is_fullscreen (bool).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS severity VARCHAR(10) NOT NULL DEFAULT 'info'");
        $this->addSql('ALTER TABLE notifications ADD COLUMN IF NOT EXISTS is_fullscreen BOOLEAN NOT NULL DEFAULT false');
        $this->addSql(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'chk_notifications_severity'
                ) THEN
                    ALTER TABLE notifications
                    ADD CONSTRAINT chk_notifications_severity
                    CHECK (severity IN ('info', 'warning', 'urgent'));
                END IF;
            END $$;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT IF EXISTS chk_notifications_severity');
        $this->addSql('ALTER TABLE notifications DROP COLUMN IF EXISTS is_fullscreen');
        $this->addSql('ALTER TABLE notifications DROP COLUMN IF EXISTS severity');
    }
}
