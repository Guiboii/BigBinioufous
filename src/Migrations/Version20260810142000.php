<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810142000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute Event.endDate (heure de fin facultative)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP end_date');
    }
}
