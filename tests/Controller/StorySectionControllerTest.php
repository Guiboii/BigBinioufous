<?php

namespace App\Tests\Controller;

use App\Entity\StorySection;
use App\Tests\Support\AppWebTestCase;

class StorySectionControllerTest extends AppWebTestCase
{
    public function testIndexIsForbiddenWithoutRoleAdmin(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/story/');

        $this->assertResponseRedirects('/desk');
    }

    public function testCreatingASectionPersistsIt(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/story/new');
        $form = $crawler->filter('form')->form();
        $form['story_section[title]'] = 'Qui sommes-nous';
        $form['story_section[content]'] = 'Contenu en markdown';
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/story/');
        $this->assertNotNull($this->entityManager->getRepository(StorySection::class)->findOneBy(['title' => 'Qui sommes-nous']));
    }

    public function testMoveUpSwapsPositionWithThePreviousSection(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $first = $this->persistSection('Intro', 'intro', 0);
        $second = $this->persistSection('Suite', 'suite', 1);

        $this->client->request('POST', '/admin/story/'.$second->getId().'/move-up', [
            '_token' => $this->csrfToken('move'.$second->getId()),
        ]);

        $first = $this->reload($first);
        $second = $this->reload($second);
        $this->assertSame(1, $first->getPosition());
        $this->assertSame(0, $second->getPosition());
    }

    private function persistSection(string $title, string $slug, int $position): StorySection
    {
        $section = (new StorySection())->setTitle($title)->setSlug($slug)->setContent('Contenu')->setPosition($position);
        $this->entityManager->persist($section);
        $this->entityManager->flush();

        return $section;
    }
}
