<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les tables voice (voix d\'un morceau) et voice_user (voix jouées par un membre)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE voice (id INT AUTO_INCREMENT NOT NULL, track_id INT NOT NULL, name VARCHAR(255) NOT NULL, filename VARCHAR(255) DEFAULT NULL, INDEX IDX_E7FB583B5ED23C43 (track_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE voice ADD CONSTRAINT FK_E7FB583B5ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE voice_user (voice_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_A050F1921672336E (voice_id), INDEX IDX_A050F192A76ED395 (user_id), PRIMARY KEY(voice_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE voice_user ADD CONSTRAINT FK_A050F1921672336E FOREIGN KEY (voice_id) REFERENCES voice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voice_user ADD CONSTRAINT FK_A050F192A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE voice_user DROP FOREIGN KEY FK_A050F1921672336E');
        $this->addSql('ALTER TABLE voice_user DROP FOREIGN KEY FK_A050F192A76ED395');
        $this->addSql('DROP TABLE voice_user');

        $this->addSql('ALTER TABLE voice DROP FOREIGN KEY FK_E7FB583B5ED23C43');
        $this->addSql('DROP TABLE voice');
    }
}
