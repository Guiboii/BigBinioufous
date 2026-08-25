<?php

namespace App\Tests\Controller;

use App\Tests\Support\AppWebTestCase;

/**
 * Member area disabled on the prod_vitrine branch (see MemberAreaDisabledSubscriber): every route this class
 * exercises is redirected to the homepage, so these assertions no longer apply here.
 *
 * @group member-area
 */
class AdminControllerTest extends AppWebTestCase
{
    public function testValidListIsForbiddenWithoutRoleAdmin(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/valid');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateBinioufousRoleGrantsIt(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $target = $this->createUser();
        $this->ensureRole('ROLE_BINIOUFOUS');

        $this->client->request('POST', '/admin/setbinioufous/'.$target->getSlug(), [
            '_token' => $this->csrfToken('create_binioufous'.$target->getId()),
        ]);

        $target = $this->reload($target);
        $this->assertContains('ROLE_BINIOUFOUS', $target->getRoles());
    }

    public function testValidatingAnAccountDoesNotGrantAnyRole(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $target = $this->createUser([], false);

        $crawler = $this->client->request('GET', '/admin/'.$target->getSlug().'/valid');
        $form = $crawler->filter('form')->form();
        $this->client->submit($form);

        $target = $this->reload($target);
        $this->assertTrue($target->getValidation());
        $this->assertSame([], $target->getRoles());
    }

    public function testToggleMembershipOnlyAffectsRoleBinioufous(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $target = $this->createUser(['ROLE_COMPTA']);
        $this->ensureRole('ROLE_BINIOUFOUS');

        $this->client->request('POST', '/admin/user/'.$target->getSlug().'/toggle-membership', [
            '_token' => $this->csrfToken('toggle_membership'.$target->getId()),
        ]);

        $target = $this->reload($target);
        $this->assertContains('ROLE_BINIOUFOUS', $target->getRoles());
        $this->assertContains('ROLE_COMPTA', $target->getRoles());
    }
}
