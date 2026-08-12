<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Retire ROLE_MEMBER (legacy, plus attribuable) et la ligne ROLE_USER (implicite, jamais assignée en pratique) de la table role';
    }

    public function up(Schema $schema): void
    {
        // ROLE_MEMBER : plus aucun moyen de l'attribuer dans l'UI depuis
        // "Rôles simplifiés" (2026-08-12), cascade sur role_user nettoie les
        // comptes qui l'avaient encore.
        // ROLE_USER : rôle implicite (User::getRoles() l'ajoute en dur,
        // jamais via la relation user<->role), sa ligne en base n'a jamais
        // servi qu'à afficher une pastille cosmétique sur les fiches admin
        // (aucun ->addRole() dessus dans le code).
        $this->addSql("DELETE FROM role WHERE title IN ('ROLE_MEMBER', 'ROLE_USER')");
    }

    public function down(Schema $schema): void
    {
        // Recrée les lignes, mais pas les associations user<->rôle perdues
        // à l'up pour ROLE_MEMBER (données, pas de trace de qui l'avait) :
        // rollback partiel, acceptable pour une migration de nettoyage.
        $this->addSql("INSERT INTO role (title, description) VALUES ('ROLE_MEMBER', 'Member'), ('ROLE_USER', 'User')");
    }
}
