<?php

namespace App\Tests\Controller;

use App\Tests\Support\AppWebTestCase;

class AdminControllerTest extends AppWebTestCase
{
    public function testValidListIsForbiddenWithoutRoleAdmin(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/valid');

        $this->assertResponseRedirects('/desk');
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

        $this->client->request('POST', '/admin/'.$target->getSlug().'/valid', [
            '_token' => $this->csrfToken('valid'.$target->getId()),
        ]);

        $target = $this->reload($target);
        $this->assertTrue($target->getValidation());
        $this->assertSame([], $target->getRoles());
    }

    public function testBulkValidValidatesEveryCheckedPendingAccount(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $first = $this->createUser([], false);
        $second = $this->createUser([], false);
        $untouched = $this->createUser([], false);

        $this->client->request('POST', '/admin/valid/bulk', [
            '_token' => $this->csrfToken('bulk_valid'),
            'bulk_action' => 'valid',
            'user_ids' => [$first->getId(), $second->getId()],
        ]);

        $this->assertResponseRedirects('/admin/valid');
        $this->assertTrue($this->reload($first)->getValidation());
        $this->assertTrue($this->reload($second)->getValidation());
        $this->assertFalse($this->reload($untouched)->getValidation());
    }

    public function testBulkRefuseDeletesOnlyPendingAccounts(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $pending = $this->createUser([], false);
        $validated = $this->createUser();
        $pendingId = $pending->getId();
        $validatedId = $validated->getId();

        $this->client->request('POST', '/admin/valid/bulk', [
            '_token' => $this->csrfToken('bulk_valid'),
            'bulk_action' => 'refuse',
            'user_ids' => [$pendingId, $validatedId],
        ]);

        $this->entityManager->clear();
        $repo = $this->entityManager->getRepository(\App\Entity\User::class);
        $this->assertNull($repo->find($pendingId));
        $this->assertNotNull($repo->find($validatedId));
    }

    public function testBulkValidIsForbiddenWithoutRoleAdmin(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);
        $pending = $this->createUser([], false);

        $this->client->request('POST', '/admin/valid/bulk', [
            'bulk_action' => 'valid',
            'user_ids' => [$pending->getId()],
        ]);

        $this->assertResponseRedirects('/desk');
        $this->assertFalse($this->reload($pending)->getValidation());
    }

    public function testToggleImageRightsConsentFlipsTheFlag(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $target = $this->createUser();

        $this->client->request('POST', '/admin/user/'.$target->getSlug().'/toggle-image-rights', [
            '_token' => $this->csrfToken('toggle_image_rights'.$target->getId()),
        ]);
        $this->assertTrue($this->reload($target)->getImageRightsConsent());

        $this->client->request('POST', '/admin/user/'.$target->getSlug().'/toggle-image-rights', [
            '_token' => $this->csrfToken('toggle_image_rights'.$target->getId()),
        ]);
        $this->assertFalse($this->reload($target)->getImageRightsConsent());
    }

    public function testToggleImageRightsConsentIsForbiddenWithoutRoleAdmin(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);
        $target = $this->createUser();

        $this->client->request('POST', '/admin/user/'.$target->getSlug().'/toggle-image-rights', [
            '_token' => $this->csrfToken('toggle_image_rights'.$target->getId()),
        ]);

        $this->assertResponseRedirects('/desk');
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
