<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fusionne ROLE_SIMPLE avec ROLE_USER (retire le rôle, role_user cascade)';
    }

    public function up(Schema $schema): void
    {
        // ON DELETE CASCADE sur role_user.role_id (Version20201108160242)
        // nettoie automatiquement les comptes qui avaient encore ROLE_SIMPLE.
        $this->addSql("DELETE FROM role WHERE title = 'ROLE_SIMPLE'");
    }

    public function down(Schema $schema): void
    {
        // Recrée le rôle, mais pas les associations user<->rôle perdues à
        // l'up (données, pas de trace de qui l'avait) : rollback partiel,
        // acceptable pour une migration de nettoyage de données.
        $this->addSql("INSERT INTO role (title, description) VALUES ('ROLE_SIMPLE', 'Simple')");
    }
}
