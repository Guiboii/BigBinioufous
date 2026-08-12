<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Espace compta : devis/factures (accounting_document + accounting_document_line) et journal de trésorerie (ledger_entry)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE accounting_document (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, number INT NOT NULL, date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', client_name VARCHAR(255) NOT NULL, client_address LONGTEXT NOT NULL, client_contact VARCHAR(255) DEFAULT NULL, correspondent_name VARCHAR(255) NOT NULL, correspondent_email VARCHAR(255) DEFAULT NULL, correspondent_phone VARCHAR(30) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_60EDA784B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE accounting_document ADD CONSTRAINT FK_60EDA784B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');

        $this->addSql('CREATE TABLE accounting_document_line (id INT AUTO_INCREMENT NOT NULL, document_id INT NOT NULL, label VARCHAR(255) NOT NULL, unit_price DOUBLE PRECISION NOT NULL, quantity INT NOT NULL, position INT NOT NULL, INDEX IDX_DC2CDB57C33F7837 (document_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE accounting_document_line ADD CONSTRAINT FK_DC2CDB57C33F7837 FOREIGN KEY (document_id) REFERENCES accounting_document (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE ledger_entry (id INT AUTO_INCREMENT NOT NULL, related_document_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', type VARCHAR(20) NOT NULL, label VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, category VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_64272A698D406B3 (related_document_id), INDEX IDX_64272A69B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A698D406B3 FOREIGN KEY (related_document_id) REFERENCES accounting_document (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A69B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A698D406B3');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A69B03A8386');
        $this->addSql('DROP TABLE ledger_entry');

        $this->addSql('ALTER TABLE accounting_document_line DROP FOREIGN KEY FK_DC2CDB57C33F7837');
        $this->addSql('DROP TABLE accounting_document_line');

        $this->addSql('ALTER TABLE accounting_document DROP FOREIGN KEY FK_60EDA784B03A8386');
        $this->addSql('DROP TABLE accounting_document');
    }
}
