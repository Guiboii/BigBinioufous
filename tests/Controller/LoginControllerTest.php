<?php

namespace App\Tests\Controller;

use App\Tests\Support\AppWebTestCase;

/**
 * Member area disabled on the prod_vitrine branch (see MemberAreaDisabledSubscriber): every route this class
 * exercises is redirected to the homepage, so these assertions no longer apply here.
 *
 * @group member-area
 */
class LoginControllerTest extends AppWebTestCase
{
    public function testJoinPageIsReachableAnonymously(): void
    {
        $this->client->request('GET', '/join');

        $this->assertResponseIsSuccessful();
    }

    public function testProfileRedirectsToJoinWhenAnonymous(): void
    {
        $this->client->request('GET', '/desk/profile');

        $this->assertResponseRedirects('/join');
    }

    public function testValidRegistrationCreatesAnUnvalidatedAccountAndLogsIn(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->filter('form')->form();
        $form['registration[nickname]'] = 'NewMember';
        $form['registration[email]'] = 'newmember@example.test';
        $form['registration[hash]'] = 'SuperSecret123';
        $form['registration[passwordConfirm]'] = 'SuperSecret123';
        $form['registration[claimsMembership]'] = '0';

        $this->client->submit($form);

        $this->assertResponseRedirects('/desk/profile');
        $user = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'newmember@example.test']);
        $this->assertNotNull($user);
        $this->assertFalse($user->getValidation());
    }

    public function testProfileUpdateWhileLoggedInPersistsChanges(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/desk/profile');
        $form = $crawler->filter('form')->form();
        $form['account[nickname]'] = $user->getNickname();
        $form['account[email]'] = $user->getEmail();
        $form['account[firstName]'] = 'Marine';
        $this->client->submit($form);

        $user = $this->reload($user);
        $this->assertResponseIsSuccessful();
        $this->assertSame('Marine', $user->getFirstName());
    }
}
