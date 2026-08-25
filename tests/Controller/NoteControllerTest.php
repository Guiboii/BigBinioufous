<?php

namespace App\Tests\Controller;

use App\Entity\Note;
use App\Entity\User;
use App\Tests\Support\AppWebTestCase;

/**
 * Member area disabled on the prod_vitrine branch (see MemberAreaDisabledSubscriber): every route this class
 * exercises is redirected to the homepage, so these assertions no longer apply here.
 *
 * @group member-area
 */
class NoteControllerTest extends AppWebTestCase
{
    public function testPrivateNoteOfAnotherAuthorIsForbiddenToRead(): void
    {
        $author = $this->createUser(['ROLE_ADMIN']);
        $note = $this->persistNote($author, 'Privée', false);
        $reader = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($reader);

        $this->client->request('GET', '/desk/notes/'.$note->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testSharedNoteOfAnotherAuthorIsReadableButNotEditable(): void
    {
        $author = $this->createUser(['ROLE_ADMIN']);
        $note = $this->persistNote($author, 'Partagée', true);
        $reader = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($reader);

        $this->client->request('GET', '/desk/notes/'.$note->getId());
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/desk/notes/'.$note->getId().'/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreatingANoteAttachesTheCurrentUserAsAuthor(): void
    {
        $author = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($author);

        $crawler = $this->client->request('GET', '/desk/notes/new');
        $form = $crawler->filter('form')->form();
        $form['note[title]'] = 'Compte-rendu';
        $form['note[content]'] = 'Contenu de la réunion';
        $this->client->submit($form);

        $this->assertResponseRedirects('/desk/notes/');
        $author = $this->reload($author);
        $note = $this->entityManager->getRepository(Note::class)->findOneBy(['title' => 'Compte-rendu']);
        $this->assertNotNull($note);
        $this->assertTrue($note->isAuthoredBy($author));
    }

    private function persistNote(User $author, string $title, bool $shared): Note
    {
        $note = (new Note())->setTitle($title)->setContent('Contenu')->setAuthor($author)->setShared($shared);
        $this->entityManager->persist($note);
        $this->entityManager->flush();

        return $note;
    }
}
