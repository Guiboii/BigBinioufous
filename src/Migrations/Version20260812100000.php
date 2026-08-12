<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table story_section (contenu Markdown éditable de la page Histoire, ROLE_ADMIN uniquement)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE story_section (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, position INT NOT NULL, UNIQUE INDEX UNIQ_STORY_SECTION_SLUG (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE story_section');
    }
}
