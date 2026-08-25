<?php

namespace App\Tests\Controller;

use App\Entity\StorySection;
use App\Tests\Support\AppWebTestCase;

class StoryControllerTest extends AppWebTestCase
{
    public function testStoryPageIsReachableAnonymously(): void
    {
        $this->client->request('GET', '/story');

        $this->assertResponseIsSuccessful();
    }

    public function testMinisiteRendersSectionsInPositionOrder(): void
    {
        $this->persistSection('Intro', 'intro', 1);
        $this->persistSection('Quoi', 'quoi', 0);

        $this->client->request('GET', '/story/mini');
        $content = (string) $this->client->getResponse()->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertLessThan(strpos($content, 'id="intro"'), strpos($content, 'id="quoi"'));
    }

    private function persistSection(string $title, string $slug, int $position): void
    {
        $section = (new StorySection())
            ->setTitle($title)
            ->setSlug($slug)
            ->setContent('Contenu')
            ->setPosition($position);

        $this->entityManager->persist($section);
        $this->entityManager->flush();
    }
}
