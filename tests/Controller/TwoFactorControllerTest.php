<?php

namespace App\Tests\Controller;

use App\Tests\Support\AppWebTestCase;
use OTPHP\TOTP;

class TwoFactorControllerTest extends AppWebTestCase
{
    public function testSetupPageIsForbiddenToNonAdminAccount(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/desk/profile/2fa');

        $this->assertResponseRedirects('/desk');
    }

    public function testEnablingWithAValidCodePersistsTheSecret(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        // Visiting the setup page first is what generates and stores the pending secret in session.
        $this->client->request('GET', '/desk/profile/2fa');

        $secret = $this->client->getRequest()->getSession()->get('totp_pending_secret');
        $code = TOTP::createFromSecret($secret)->now();

        $this->client->request('POST', '/desk/profile/2fa/enable', [
            '_token' => $this->csrfToken('two_factor_enable'),
            'code' => $code,
        ]);

        $admin = $this->reload($admin);
        $this->assertTrue($admin->isTotpAuthenticationEnabled());
    }

    public function testEnablingWithAnInvalidCodeDoesNotPersistASecret(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $this->client->request('GET', '/desk/profile/2fa');

        $this->client->request('POST', '/desk/profile/2fa/enable', [
            '_token' => $this->csrfToken('two_factor_enable'),
            'code' => '000000',
        ]);

        $admin = $this->reload($admin);
        $this->assertFalse($admin->isTotpAuthenticationEnabled());
    }
}
