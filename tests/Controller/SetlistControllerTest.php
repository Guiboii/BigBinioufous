<?php

namespace App\Tests\Controller;

use App\Entity\SetlistItem;
use App\Tests\Support\AppWebTestCase;

class SetlistControllerTest extends AppWebTestCase
{
    public function testAddingASongPersistsItAtTheNextPosition(): void
    {
        $user = $this->createUser(['ROLE_BINIOUFOUS']);
        $this->client->loginUser($user);

        $this->client->request('POST', '/desk/files/music/setlist', [
            '_token' => $this->csrfToken('create_setlist_item'),
            'title' => 'Fanfare improbable',
        ]);

        $this->assertResponseRedirects('/music');
        $item = $this->entityManager->getRepository(SetlistItem::class)->findOneBy(['title' => 'Fanfare improbable']);
        $this->assertNotNull($item);
        $this->assertSame(0, $item->getPosition());
    }

    public function testMoveUpSwapsPositionWithThePreviousSong(): void
    {
        $user = $this->createUser(['ROLE_BINIOUFOUS']);
        $this->client->loginUser($user);
        $first = $this->persistItem('Premier', 0);
        $second = $this->persistItem('Deuxième', 1);

        $this->client->request('POST', '/desk/files/music/setlist/'.$second->getId().'/move-up', [
            '_token' => $this->csrfToken('move_setlist_item'.$second->getId()),
        ]);

        $first = $this->reload($first);
        $second = $this->reload($second);
        $this->assertSame(1, $first->getPosition());
        $this->assertSame(0, $second->getPosition());
    }

    private function persistItem(string $title, int $position): SetlistItem
    {
        $item = (new SetlistItem())->setTitle($title)->setPosition($position);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }
}
