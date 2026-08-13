<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Espace compta : table client (fiches commanditaires réutilisables) + lien facture -> devis d\'origine sur accounting_document';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address LONGTEXT DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE accounting_document ADD client_id INT DEFAULT NULL, ADD source_quote_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE accounting_document ADD CONSTRAINT FK_60EDA78419EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE accounting_document ADD CONSTRAINT FK_60EDA784A0BB92C1 FOREIGN KEY (source_quote_id) REFERENCES accounting_document (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_60EDA78419EB6921 ON accounting_document (client_id)');
        $this->addSql('CREATE INDEX IDX_60EDA784A0BB92C1 ON accounting_document (source_quote_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounting_document DROP FOREIGN KEY FK_60EDA78419EB6921');
        $this->addSql('ALTER TABLE accounting_document DROP FOREIGN KEY FK_60EDA784A0BB92C1');
        $this->addSql('DROP INDEX IDX_60EDA78419EB6921 ON accounting_document');
        $this->addSql('DROP INDEX IDX_60EDA784A0BB92C1 ON accounting_document');
        $this->addSql('ALTER TABLE accounting_document DROP client_id, DROP source_quote_id');

        $this->addSql('DROP TABLE client');
    }
}
