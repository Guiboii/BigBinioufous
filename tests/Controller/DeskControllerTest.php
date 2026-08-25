<?php

namespace App\Tests\Controller;

use App\Tests\Support\AppWebTestCase;

/**
 * Member area disabled on the prod_vitrine branch (see MemberAreaDisabledSubscriber): every route this class
 * exercises is redirected to the homepage, so these assertions no longer apply here.
 *
 * @group member-area
 */
class DeskControllerTest extends AppWebTestCase
{
    public function testDeskIsNotReachableAnonymously(): void
    {
        $this->client->request('GET', '/desk');

        $this->assertResponseRedirects('/join');
    }

    public function testMusicSpaceIsReachableByABinioufousAccount(): void
    {
        $user = $this->createUser(['ROLE_BINIOUFOUS']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/desk/files/music');

        $this->assertResponseIsSuccessful();
    }

    public function testMusicSpaceIsForbiddenToASimpleValidatedAccount(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/desk/files/music');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccountingSpaceRequiresRoleCompta(): void
    {
        $binioufous = $this->createUser(['ROLE_BINIOUFOUS']);
        $this->client->loginUser($binioufous);
        $this->client->request('GET', '/desk/files/accounting');
        $this->assertResponseStatusCodeSame(403);

        $accountant = $this->createUser(['ROLE_COMPTA']);
        $this->client->loginUser($accountant);
        $this->client->request('GET', '/desk/files/accounting');
        $this->assertResponseIsSuccessful();
    }

    public function testFolderFromAnotherSpaceIsNotFound(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        // Visiting the admin space first makes the file manager lazily create its root folder.
        $this->client->request('GET', '/desk/files/admin');
        $adminRoot = $this->entityManager->getRepository(\App\Entity\Folder::class)->findOneBy(['space' => 'admin']);

        $this->client->request('GET', '/desk/files/other?folder='.$adminRoot->getId());

        $this->assertResponseStatusCodeSame(404);
    }
}
