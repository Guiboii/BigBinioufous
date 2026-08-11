<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute User.memberCardNumber (numéro de carte d\'adhérent·e, facultatif)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD member_card_number VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP member_card_number');
    }
}
