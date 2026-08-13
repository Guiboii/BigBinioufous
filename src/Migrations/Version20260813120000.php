<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corbeille (Folder/Document.deleted_at) + taille de fichier (Document.size) pour le gestionnaire de fichiers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE folder ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD deleted_at DATETIME DEFAULT NULL, ADD size INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE folder DROP deleted_at');
        $this->addSql('ALTER TABLE document DROP deleted_at, DROP size');
    }
}
