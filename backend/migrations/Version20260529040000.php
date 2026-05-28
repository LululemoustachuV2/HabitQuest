<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * V2.4 — Boutique or : items vendables et prix boutique.
 */
final class Version20260529040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Items — is_sellable et shop_price pour la boutique.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE items ADD is_sellable BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE items ADD shop_price INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE items DROP COLUMN shop_price');
        $this->addSql('ALTER TABLE items DROP COLUMN is_sellable');
    }
}
