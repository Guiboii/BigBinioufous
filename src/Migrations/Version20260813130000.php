<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table note (outil de prise de note du bureau/conseil, /desk/notes, ROLE_ADMIN/ROLE_COMPTA)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, shared TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_NOTE_AUTHOR (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_NOTE_AUTHOR FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_NOTE_AUTHOR');
        $this->addSql('DROP TABLE note');
    }
}
