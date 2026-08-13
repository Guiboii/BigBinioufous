<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Espace compta : accounting_document.excluded_from_invoicing, pour retirer un devis du sélecteur "Nouvelle facture" sans le supprimer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounting_document ADD excluded_from_invoicing TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounting_document DROP excluded_from_invoicing');
    }
}
