<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les tables carpool_offer et carpool_offer_passenger (service de covoiturage, /desk/carpool, ROADMAP.md phase 7)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE carpool_offer (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, driver_id INT NOT NULL, departure_location VARCHAR(255) NOT NULL, departure_time DATETIME DEFAULT NULL, seats_total SMALLINT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_CARPOOL_OFFER_EVENT (event_id), INDEX IDX_CARPOOL_OFFER_DRIVER (driver_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE carpool_offer ADD CONSTRAINT FK_CARPOOL_OFFER_EVENT FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE carpool_offer ADD CONSTRAINT FK_CARPOOL_OFFER_DRIVER FOREIGN KEY (driver_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE carpool_offer_passenger (carpool_offer_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_CARPOOL_OFFER_PASSENGER_OFFER (carpool_offer_id), INDEX IDX_CARPOOL_OFFER_PASSENGER_USER (user_id), PRIMARY KEY(carpool_offer_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE carpool_offer_passenger ADD CONSTRAINT FK_CARPOOL_OFFER_PASSENGER_OFFER FOREIGN KEY (carpool_offer_id) REFERENCES carpool_offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE carpool_offer_passenger ADD CONSTRAINT FK_CARPOOL_OFFER_PASSENGER_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE carpool_offer_passenger DROP FOREIGN KEY FK_CARPOOL_OFFER_PASSENGER_OFFER');
        $this->addSql('ALTER TABLE carpool_offer_passenger DROP FOREIGN KEY FK_CARPOOL_OFFER_PASSENGER_USER');
        $this->addSql('DROP TABLE carpool_offer_passenger');

        $this->addSql('ALTER TABLE carpool_offer DROP FOREIGN KEY FK_CARPOOL_OFFER_EVENT');
        $this->addSql('ALTER TABLE carpool_offer DROP FOREIGN KEY FK_CARPOOL_OFFER_DRIVER');
        $this->addSql('DROP TABLE carpool_offer');
    }
}
