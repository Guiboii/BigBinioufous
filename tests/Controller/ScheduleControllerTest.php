<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use App\Tests\Support\AppWebTestCase;

class ScheduleControllerTest extends AppWebTestCase
{
    public function testAnonymousVisitorOnlySeesConcerts(): void
    {
        $this->persistEvent('Concert public', 'concert');
        $this->persistEvent('Répète interne', 'rehearsal');

        $this->client->request('GET', '/schedule');
        $content = (string) $this->client->getResponse()->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Concert public', $content);
        $this->assertStringNotContainsString('Répète interne', $content);
    }

    public function testLoggedInMemberSeesAllEventTypes(): void
    {
        $this->persistEvent('Concert public', 'concert');
        $this->persistEvent('Répète interne', 'rehearsal');
        $user = $this->createUser();

        $this->client->loginUser($user);
        $this->client->request('GET', '/schedule');
        $content = (string) $this->client->getResponse()->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Répète interne', $content);
    }

    public function testIcsExportOfANonConcertEventIsNotFoundAnonymously(): void
    {
        $event = $this->persistEvent('Répète interne', 'rehearsal');

        $this->client->request('GET', '/schedule/event/'.$event->getId().'.ics');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBoardMeetingIsHiddenFromScheduleEvenForLoggedInMembers(): void
    {
        $this->persistEvent('Conseil de bureau', 'board_meeting');
        $this->client->loginUser($this->createUser());

        $this->client->request('GET', '/schedule');
        $content = (string) $this->client->getResponse()->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('Conseil de bureau', $content);
    }

    public function testBoardMeetingIsShownOnScheduleForAdmins(): void
    {
        $this->persistEvent('Conseil de bureau', 'board_meeting');
        $this->client->loginUser($this->createUser(['ROLE_ADMIN']));

        $this->client->request('GET', '/schedule');
        $content = (string) $this->client->getResponse()->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Conseil de bureau', $content);
    }

    public function testIcsExportOfABoardMeetingIsNotFoundForNonAdmins(): void
    {
        $event = $this->persistEvent('Conseil de bureau', 'board_meeting');
        $this->client->loginUser($this->createUser());

        $this->client->request('GET', '/schedule/event/'.$event->getId().'.ics');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testIcsExportOfABoardMeetingWorksForAdmins(): void
    {
        $event = $this->persistEvent('Conseil de bureau', 'board_meeting');
        $this->client->loginUser($this->createUser(['ROLE_ADMIN']));

        $this->client->request('GET', '/schedule/event/'.$event->getId().'.ics');

        $this->assertResponseIsSuccessful();
    }

    private function persistEvent(string $title, string $type): Event
    {
        $event = (new Event())
            ->setTitle($title)
            ->setLocation('Local des Binioufous')
            ->setType($type)
            ->setDate(new \DateTimeImmutable('+1 week'));

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }
}
