<?php

namespace App\Tests\Controller;

use App\Entity\Folder;
use App\Repository\FolderRepository;
use App\Tests\Support\AppWebTestCase;

/**
 * Member area disabled on the prod_vitrine branch (see MemberAreaDisabledSubscriber): every route this class
 * exercises is redirected to the homepage, so these assertions no longer apply here.
 *
 * @group member-area
 */
class FolderControllerTest extends AppWebTestCase
{
    public function testBinioufousCanCreateAFolderInMusicSpace(): void
    {
        $user = $this->createUser(['ROLE_BINIOUFOUS']);
        $this->client->loginUser($user);
        $root = $this->root('music');

        $this->client->request('POST', '/desk/files/music/folders', [
            '_token' => $this->csrfToken('create_folder'.$root->getId()),
            'parent' => $root->getId(),
            'name' => 'Medley breton',
        ]);

        $this->assertResponseRedirects();
        $this->assertNotNull($this->entityManager->getRepository(Folder::class)->findOneBy(['name' => 'Medley breton']));
    }

    public function testAccountWithoutWriteRoleIsForbiddenToCreateAFolder(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);
        $root = $this->root('music');

        $this->client->request('POST', '/desk/files/music/folders', [
            '_token' => $this->csrfToken('create_folder'.$root->getId()),
            'parent' => $root->getId(),
            'name' => 'Medley breton',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeletingAFolderDoesNotTrashItsChildren(): void
    {
        $user = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($user);
        $parent = $this->folder('music', 'Parent');
        $child = $this->folder('music', 'Enfant', $parent);

        $this->client->request('DELETE', '/desk/files/music/folders/'.$parent->getId(), [
            '_token' => $this->csrfToken('delete_folder'.$parent->getId()),
        ]);

        $parent = $this->reload($parent);
        $child = $this->reload($child);
        $this->assertTrue($parent->isDeleted());
        $this->assertFalse($child->isDeleted());
    }

    public function testMovingAFolderIntoItsOwnDescendantIsRejected(): void
    {
        $user = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($user);
        $root = $this->root('music');
        $parent = $this->folder('music', 'Parent');
        $child = $this->folder('music', 'Enfant', $parent);

        $this->client->request('POST', '/desk/files/music/folders/'.$parent->getId().'/move', [
            '_token' => $this->csrfToken('move_folder'.$parent->getId()),
            'target' => $child->getId(),
        ]);

        $parent = $this->reload($parent);
        $this->assertSame($root->getId(), $parent->getParent()->getId());
    }

    private function root(string $space): Folder
    {
        return static::getContainer()->get(FolderRepository::class)->findOrCreateRoot($space, $this->entityManager);
    }

    private function folder(string $space, string $name, ?Folder $parent = null): Folder
    {
        $folder = (new Folder())->setName($name)->setSpace($space)->setParent($parent ?? $this->root($space));
        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        return $folder;
    }
}
