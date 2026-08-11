<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811171000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table document_user (favoris sur les documents audio de /desk/music)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE document_user (document_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_2A275ADAC33F7837 (document_id), INDEX IDX_2A275ADAA76ED395 (user_id), PRIMARY KEY(document_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE document_user ADD CONSTRAINT FK_2A275ADAC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document_user ADD CONSTRAINT FK_2A275ADAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_user DROP FOREIGN KEY FK_2A275ADAC33F7837');
        $this->addSql('ALTER TABLE document_user DROP FOREIGN KEY FK_2A275ADAA76ED395');
        $this->addSql('DROP TABLE document_user');
    }
}
