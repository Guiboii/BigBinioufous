<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '2FA (scheb/2fa-bundle) : user.totp_secret, nullable = 2FA désactivée pour ce compte';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD totp_secret VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP totp_secret');
    }
}
