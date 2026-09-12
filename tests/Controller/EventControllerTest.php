<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use App\Tests\Support\AppWebTestCase;

class EventControllerTest extends AppWebTestCase
{
    public function testIndexIsForbiddenWithoutRoleAdmin(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/event/');

        $this->assertResponseRedirects('/desk');
    }

    public function testCreatingAnEventPersistsIt(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/event/new');
        $form = $crawler->filter('form')->form();
        $form['event[title]'] = 'Fest-noz';
        $form['event[location]'] = 'Place du village';
        $form['event[type]'] = 'concert';
        $form['event[date]'] = (new \DateTimeImmutable('+2 weeks'))->format('Y-m-d\TH:i');
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/event/');
        $this->assertNotNull($this->entityManager->getRepository(Event::class)->findOneBy(['title' => 'Fest-noz']));
    }

    public function testDeletingAnEventRemovesIt(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $event = (new Event())->setTitle('À supprimer')->setLocation('Ici')->setType('other')->setDate(new \DateTimeImmutable('+1 week'));
        $this->entityManager->persist($event);
        $this->entityManager->flush();
        $id = $event->getId();

        $this->client->request('DELETE', '/admin/event/'.$id, [
            '_token' => $this->csrfToken('delete'.$id),
        ]);

        $this->assertNull($this->entityManager->getRepository(Event::class)->find($id));
    }
}
