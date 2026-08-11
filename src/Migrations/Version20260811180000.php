<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Multi-espaces sur /desk/files (musique/admin/compta) : folder.space + document.folder_id devient obligatoire';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE folder ADD space VARCHAR(20) NOT NULL DEFAULT 'music'");

        // L'arborescence existante (créée avant les espaces, uniquement pour
        // la musique) n'avait pas de dossier racine réel : parent_id/folder_id
        // NULL faisait office de racine implicite. On crée une vraie racine
        // "Musique" et on y rattache tout ce qui existait déjà à la racine.
        $this->addSql("INSERT INTO folder (name, parent_id, space) VALUES ('Musique', NULL, 'music')");
        $this->addSql('SET @music_root_id = LAST_INSERT_ID()');
        $this->addSql('UPDATE folder SET parent_id = @music_root_id WHERE parent_id IS NULL AND id <> @music_root_id');
        $this->addSql('UPDATE document SET folder_id = @music_root_id WHERE folder_id IS NULL');

        $this->addSql('ALTER TABLE document MODIFY folder_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document MODIFY folder_id INT DEFAULT NULL');

        $this->addSql("SET @music_root_id = (SELECT id FROM folder WHERE parent_id IS NULL AND space = 'music' AND name = 'Musique' LIMIT 1)");
        $this->addSql('UPDATE document SET folder_id = NULL WHERE folder_id = @music_root_id');
        $this->addSql('UPDATE folder SET parent_id = NULL WHERE parent_id = @music_root_id');
        $this->addSql('DELETE FROM folder WHERE id = @music_root_id');

        $this->addSql('ALTER TABLE folder DROP space');
    }
}
