<?php

namespace App\Tests\Controller;

use App\Entity\Document;
use App\Entity\Folder;
use App\Repository\FolderRepository;
use App\Tests\Support\AppWebTestCase;

class BulkActionControllerTest extends AppWebTestCase
{
    public function testBulkDeleteTrashesFoldersAndDocumentsTogether(): void
    {
        $user = $this->createUser(['ROLE_BINIOUFOUS']);
        $this->client->loginUser($user);
        $root = static::getContainer()->get(FolderRepository::class)->findOrCreateRoot('music', $this->entityManager);
        $folder = (new Folder())->setName('Dossier')->setSpace('music')->setParent($root);
        $document = (new Document())->setName('Fichier')->setFilename('fichier.txt')->setMimeType('text/plain')->setFolder($root);
        $this->entityManager->persist($folder);
        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $this->client->request('POST', '/desk/files/music/bulk/delete', [
            '_token' => $this->csrfToken('bulk_deletemusic'),
            'folder_ids' => [$folder->getId()],
            'document_ids' => [$document->getId()],
        ]);

        $folder = $this->reload($folder);
        $document = $this->reload($document);
        $this->assertTrue($folder->isDeleted());
        $this->assertTrue($document->isDeleted());
    }

    public function testBulkDeleteIsForbiddenWithoutWriteRole(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('POST', '/desk/files/music/bulk/delete', [
            '_token' => $this->csrfToken('bulk_deletemusic'),
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
