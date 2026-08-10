<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260810134435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table event (planning)';
    }

    public function up(Schema $schema): void
    {
        // Uniquement la table event : le diff auto-généré incluait aussi 2
        // ALTER TABLE sur track/user (dérive de schéma préexistante, sans
        // rapport avec cette entité), retirés pour ne pas mélanger les
        // deux dans la même migration.
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', description LONGTEXT DEFAULT NULL, poster_filename VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event');
    }
}
