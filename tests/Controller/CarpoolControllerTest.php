<?php

namespace App\Tests\Controller;

use App\Entity\CarpoolOffer;
use App\Entity\Event;
use App\Entity\User;
use App\Tests\Support\AppWebTestCase;

class CarpoolControllerTest extends AppWebTestCase
{
    public function testIndexIsAccessibleToAnyLoggedInMemberWithoutASpecificRole(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/desk/carpool/');

        $this->assertResponseIsSuccessful();
    }

    public function testCreatingAnOfferAttachesTheCurrentUserAsDriver(): void
    {
        $driver = $this->createUser();
        $this->client->loginUser($driver);
        $event = $this->persistEvent('2099-06-01');

        $crawler = $this->client->request('GET', '/desk/carpool/new');
        $form = $crawler->filter('form')->form();
        $form['carpool_offer[event]'] = $event->getId();
        $form['carpool_offer[departureLocation]'] = 'Parking de la mairie';
        $form['carpool_offer[seatsTotal]'] = 3;
        $this->client->submit($form);

        $this->assertResponseRedirects('/desk/carpool/');
        $driver = $this->reload($driver);
        $offer = $this->entityManager->getRepository(CarpoolOffer::class)->findOneBy(['departureLocation' => 'Parking de la mairie']);
        $this->assertNotNull($offer);
        $this->assertTrue($offer->isDriver($driver));
        $this->assertSame(3, $offer->getSeatsAvailable());
    }

    public function testJoiningAndLeavingTogglesThePassengerList(): void
    {
        $driver = $this->createUser();
        $passenger = $this->createUser();
        $event = $this->persistEvent('2099-06-01');
        $offer = $this->persistOffer($event, $driver, 2);

        $this->client->loginUser($passenger);
        $token = $this->csrfToken('toggle_join'.$offer->getId());
        $this->client->request('POST', '/desk/carpool/'.$offer->getId().'/join', ['_token' => $token]);
        $this->assertResponseRedirects('/desk/carpool/');

        $offer = $this->reload($offer);
        $this->assertSame(1, $offer->getSeatsAvailable());

        $token = $this->csrfToken('toggle_join'.$offer->getId());
        $this->client->request('POST', '/desk/carpool/'.$offer->getId().'/join', ['_token' => $token]);

        $offer = $this->reload($offer);
        $this->assertSame(2, $offer->getSeatsAvailable());
    }

    public function testDriverCannotJoinTheirOwnOfferAsAPassenger(): void
    {
        $driver = $this->createUser();
        $event = $this->persistEvent('2099-06-01');
        $offer = $this->persistOffer($event, $driver, 2);

        $this->client->loginUser($driver);
        $token = $this->csrfToken('toggle_join'.$offer->getId());
        $this->client->request('POST', '/desk/carpool/'.$offer->getId().'/join', ['_token' => $token]);

        $offer = $this->reload($offer);
        $this->assertSame(2, $offer->getSeatsAvailable());
    }

    public function testJoiningAFullOfferIsRejected(): void
    {
        $driver = $this->createUser();
        $alreadyIn = $this->createUser();
        $latecomer = $this->createUser();
        $event = $this->persistEvent('2099-06-01');
        $offer = $this->persistOffer($event, $driver, 1, [$alreadyIn]);

        $this->client->loginUser($latecomer);
        $token = $this->csrfToken('toggle_join'.$offer->getId());
        $this->client->request('POST', '/desk/carpool/'.$offer->getId().'/join', ['_token' => $token]);

        $offer = $this->reload($offer);
        $this->assertTrue($offer->isFull());
        $this->assertFalse($offer->hasPassenger($this->reload($latecomer)));
    }

    public function testOnlyTheDriverOrAnAdminCanDeleteAnOffer(): void
    {
        $driver = $this->createUser();
        $someoneElse = $this->createUser();
        $event = $this->persistEvent('2099-06-01');
        $offer = $this->persistOffer($event, $driver, 2);

        $this->client->loginUser($someoneElse);
        $token = $this->csrfToken('delete'.$offer->getId());
        $this->client->request('POST', '/desk/carpool/'.$offer->getId(), ['_method' => 'DELETE', '_token' => $token]);

        $this->assertResponseRedirects('/desk');
    }

    private function persistEvent(string $date): Event
    {
        $event = (new Event())
            ->setTitle('Résidence')
            ->setLocation('Ailleurs')
            ->setType('other')
            ->setDate(new \DateTimeImmutable($date));
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    private function persistOffer(Event $event, User $driver, int $seatsTotal, array $passengers = []): CarpoolOffer
    {
        $offer = (new CarpoolOffer())
            ->setEvent($event)
            ->setDriver($driver)
            ->setDepartureLocation('Parking de la mairie')
            ->setSeatsTotal($seatsTotal);

        foreach ($passengers as $passenger) {
            $offer->addPassenger($passenger);
        }

        $this->entityManager->persist($offer);
        $this->entityManager->flush();

        return $offer;
    }
}
