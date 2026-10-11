<?php

namespace App\Tests\Controller;

use App\Entity\Document;
use App\Repository\FolderRepository;
use App\Tests\Support\AppWebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentControllerTest extends AppWebTestCase
{
    public function testUploadWithDisallowedMimetypeIsRejected(): void
    {
        $user = $this->createUser(['ROLE_BINIOUFOUS']);
        $this->client->loginUser($user);
        $file = $this->tempFile('archive.zip', "PK\x03\x04rest of a fake zip");

        $this->client->request('POST', '/desk/files/music/documents', [
            '_token' => $this->csrfToken('quick_upload'),
        ], ['file' => $file]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_mimetype"}', (string) $this->client->getResponse()->getContent());
    }

    public function testUploadWithAllowedMimetypePersistsADocument(): void
    {
        $user = $this->createUser(['ROLE_BINIOUFOUS']);
        $this->client->loginUser($user);
        $file = $this->tempFile('partition.txt', 'Contenu texte de la partition');

        $this->client->request('POST', '/desk/files/music/documents', [
            '_token' => $this->csrfToken('quick_upload'),
        ], ['file' => $file]);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $this->entityManager->getRepository(Document::class)->findAll());
    }

    public function testUploadLandsInTheViewedFolderEvenWithADuplicateName(): void
    {
        $user = $this->createUser(['ROLE_BINIOUFOUS']);
        $this->client->loginUser($user);
        $folders = static::getContainer()->get(FolderRepository::class);
        $root = $folders->findOrCreateRoot('music', $this->entityManager);
        // Two sibling folders with the same name: resolving the destination by name would pick the first one.
        $this->createFolder('Set', $root);
        $viewed = $this->createFolder('Set', $root);

        $this->client->request('POST', '/desk/files/music/documents', [
            '_token' => $this->csrfToken('quick_upload'),
            'folder' => $viewed->getId(),
            'path' => 'Medley',
        ], ['file' => $this->tempFile('voix.txt', 'Une voix')]);

        $this->assertResponseIsSuccessful();
        $document = $this->entityManager->getRepository(Document::class)->findOneBy([]);
        $this->assertSame('Medley', $document->getFolder()->getName());
        $this->assertSame($viewed->getId(), $document->getFolder()->getParent()->getId());
    }

    public function testUploadIntoAFolderOfAnotherSpaceIsRejected(): void
    {
        $user = $this->createUser(['ROLE_BINIOUFOUS']);
        $this->client->loginUser($user);
        $otherRoot = static::getContainer()->get(FolderRepository::class)->findOrCreateRoot('accounting', $this->entityManager);

        $this->client->request('POST', '/desk/files/music/documents', [
            '_token' => $this->csrfToken('quick_upload'),
            'folder' => $otherRoot->getId(),
        ], ['file' => $this->tempFile('voix.txt', 'Une voix')]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_folder"}', (string) $this->client->getResponse()->getContent());
        $this->assertCount(0, $this->entityManager->getRepository(Document::class)->findAll());
    }

    private function createFolder(string $name, \App\Entity\Folder $parent): \App\Entity\Folder
    {
        $folder = (new \App\Entity\Folder())->setName($name)->setParent($parent)->setSpace($parent->getSpace());
        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        return $folder;
    }

    public function testFavoriteToggleIsAllowedWithoutWriteRole(): void
    {
        // "other" space: read is open to binioufous, write is admin-only (Folder::WRITE_ROLES), so this account
        // can reach the page but has no write role, unlike "music" where read and write require the same roles.
        $user = $this->createUser(['ROLE_BINIOUFOUS']);
        $this->client->loginUser($user);
        $document = $this->document('other');

        $this->client->request('POST', '/desk/files/other/documents/'.$document->getId().'/favorite', [
            '_token' => $this->csrfToken('toggle_favorite'.$document->getId()),
        ]);

        $document = $this->reload($document);
        $this->assertResponseRedirects();
        $this->assertTrue($document->getFavoritedBy()->contains($user));
    }

    public function testMovingADocumentToAFolderOfAnotherSpaceIsNotFound(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $document = $this->document('music');
        $otherRoot = static::getContainer()->get(FolderRepository::class)->findOrCreateRoot('other', $this->entityManager);

        $this->client->request('POST', '/desk/files/music/documents/'.$document->getId().'/move', [
            '_token' => $this->csrfToken('move_document'.$document->getId()),
            'target' => $otherRoot->getId(),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    private function document(string $space): Document
    {
        $root = static::getContainer()->get(FolderRepository::class)->findOrCreateRoot($space, $this->entityManager);
        $document = (new Document())
            ->setName('Partition')
            ->setFilename('partition-'.uniqid().'.txt')
            ->setMimeType('text/plain')
            ->setFolder($root);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

    private function tempFile(string $originalName, string $content): UploadedFile
    {
        $path = sys_get_temp_dir().'/'.uniqid().'-'.$originalName;
        file_put_contents($path, $content);

        return new UploadedFile($path, $originalName, null, null, true);
    }
}
