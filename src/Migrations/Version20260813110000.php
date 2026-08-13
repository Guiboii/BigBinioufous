<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fusion Track/Voice dans Folder/Document : setlist_item (setlist de /music) + document_played_by, drop track/voice';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE document_played_by (document_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_7E5E602FC33F7837 (document_id), INDEX IDX_7E5E602FA76ED395 (user_id), PRIMARY KEY(document_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE setlist_item (id INT AUTO_INCREMENT NOT NULL, artist_id INT DEFAULT NULL, folder_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, youtube_url VARCHAR(255) DEFAULT NULL, position INT NOT NULL, INDEX IDX_4AFC64AAB7970CF8 (artist_id), INDEX IDX_4AFC64AA162CB942 (folder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE document_played_by ADD CONSTRAINT FK_7E5E602FC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document_played_by ADD CONSTRAINT FK_7E5E602FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE setlist_item ADD CONSTRAINT FK_4AFC64AAB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id)');
        $this->addSql('ALTER TABLE setlist_item ADD CONSTRAINT FK_4AFC64AA162CB942 FOREIGN KEY (folder_id) REFERENCES folder (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE track DROP FOREIGN KEY FK_D6E3F8A6B7970CF8');
        $this->addSql('ALTER TABLE voice_user DROP FOREIGN KEY FK_A050F1921672336E');
        $this->addSql('ALTER TABLE voice_user DROP FOREIGN KEY FK_A050F192A76ED395');
        $this->addSql('ALTER TABLE voice DROP FOREIGN KEY FK_E7FB583B5ED23C43');
        $this->addSql('DROP TABLE track');
        $this->addSql('DROP TABLE voice_user');
        $this->addSql('DROP TABLE voice');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE track (id INT AUTO_INCREMENT NOT NULL, artist_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, track_filename VARCHAR(255) DEFAULT NULL, minutes INT NOT NULL, seconds INT NOT NULL, INDEX IDX_D6E3F8A6B7970CF8 (artist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE voice (id INT AUTO_INCREMENT NOT NULL, track_id INT NOT NULL, name VARCHAR(255) NOT NULL, filename VARCHAR(255) DEFAULT NULL, INDEX IDX_E7FB583B5ED23C43 (track_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE voice_user (voice_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_A050F1921672336E (voice_id), INDEX IDX_A050F192A76ED395 (user_id), PRIMARY KEY(voice_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A6B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id)');
        $this->addSql('ALTER TABLE voice ADD CONSTRAINT FK_E7FB583B5ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voice_user ADD CONSTRAINT FK_A050F1921672336E FOREIGN KEY (voice_id) REFERENCES voice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voice_user ADD CONSTRAINT FK_A050F192A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE document_played_by DROP FOREIGN KEY FK_7E5E602FC33F7837');
        $this->addSql('ALTER TABLE document_played_by DROP FOREIGN KEY FK_7E5E602FA76ED395');
        $this->addSql('ALTER TABLE setlist_item DROP FOREIGN KEY FK_4AFC64AAB7970CF8');
        $this->addSql('ALTER TABLE setlist_item DROP FOREIGN KEY FK_4AFC64AA162CB942');
        $this->addSql('DROP TABLE document_played_by');
        $this->addSql('DROP TABLE setlist_item');
    }
}
