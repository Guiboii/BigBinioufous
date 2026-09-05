<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute user.image_rights_consent (accord droit à l\'image, coché par un·e admin)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD image_rights_consent TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP image_rights_consent');
    }
}
