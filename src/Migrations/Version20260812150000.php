<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Simplifie l\'inscription : firstName/lastName/city/gender/birth/country facultatifs, retire wish, ajoute claimsMembership';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user MODIFY first_name VARCHAR(255) NULL, MODIFY last_name VARCHAR(255) NULL, MODIFY city VARCHAR(255) NULL, MODIFY gender VARCHAR(255) NULL, MODIFY birth DATE NULL, MODIFY country VARCHAR(255) NULL, DROP COLUMN wish, ADD COLUMN claims_membership TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user MODIFY first_name VARCHAR(255) NOT NULL, MODIFY last_name VARCHAR(255) NOT NULL, MODIFY city VARCHAR(255) NOT NULL, MODIFY gender VARCHAR(255) NOT NULL, MODIFY birth DATE NOT NULL, MODIFY country VARCHAR(255) NOT NULL, ADD COLUMN wish VARCHAR(255) NOT NULL, DROP COLUMN claims_membership');
    }
}
