<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute User.otherInstrumentDetail (précision libre pour l\'instrument "Autre")';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD COLUMN other_instrument_detail VARCHAR(255) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN other_instrument_detail');
    }
}
