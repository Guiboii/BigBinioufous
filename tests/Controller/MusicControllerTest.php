<?php

namespace App\Tests\Controller;

use App\Entity\SetlistItem;
use App\Tests\Support\AppWebTestCase;

class MusicControllerTest extends AppWebTestCase
{
    public function testMusicPageIsReachableAnonymously(): void
    {
        $this->client->request('GET', '/music');

        $this->assertResponseIsSuccessful();
    }

    public function testSetlistIsRenderedInPositionOrder(): void
    {
        $this->persistItem('Deuxième morceau', 1);
        $this->persistItem('Premier morceau', 0);

        $this->client->request('GET', '/music');
        $content = (string) $this->client->getResponse()->getContent();

        $this->assertLessThan(strpos($content, 'Deuxième morceau'), strpos($content, 'Premier morceau'));
    }

    private function persistItem(string $title, int $position): SetlistItem
    {
        $item = (new SetlistItem())->setTitle($title)->setPosition($position);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }
}
