<?php

namespace App\Tests\Controller;

use App\Tests\Support\AppWebTestCase;

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
        $this->resetRegistrationLimiter('10.0.1.1');
        $this->submitRegistration('newmember@example.test', '10.0.1.1');

        $this->assertResponseRedirects('/desk/profile');
        $user = $this->findUserByEmail('newmember@example.test');
        $this->assertNotNull($user);
        $this->assertFalse($user->getValidation());
    }

    public function testRegistrationWithFilledHoneypotCreatesNothing(): void
    {
        $this->submitRegistration('bot-honeypot@example.test', '10.0.1.2', ['register_hp' => 'http://spam.example']);

        $this->assertResponseRedirects('/join');
        $this->assertNull($this->findUserByEmail('bot-honeypot@example.test'));
    }

    public function testRegistrationSubmittedTooFastCreatesNothing(): void
    {
        $this->submitRegistration('bot-fast@example.test', '10.0.1.3', ['register_ts' => (string) time()]);

        $this->assertResponseRedirects('/join');
        $this->assertNull($this->findUserByEmail('bot-fast@example.test'));
    }

    public function testRegistrationIsRateLimitedPerIp(): void
    {
        $this->resetRegistrationLimiter('10.0.1.4');
        for ($i = 1; $i <= 3; ++$i) {
            $this->submitRegistration("ratelimit$i@example.test", '10.0.1.4');
            $this->assertResponseRedirects('/desk/profile');
            // Each successful signup logs the new account in: log out so the next GET /register starts anonymous again.
            $this->client->getCookieJar()->clear();
        }

        $this->submitRegistration('ratelimit4@example.test', '10.0.1.4');

        $this->assertResponseStatusCodeSame(429);
        $this->assertNull($this->findUserByEmail('ratelimit4@example.test'));
    }

    // The limiter's cache pool survives between tests and between runs (filesystem), unlike the fresh-schema setUp(): without a reset, repeated local runs would hit the limit.
    private function resetRegistrationLimiter(string $ip): void
    {
        static::getContainer()->get('limiter.registration')->create($ip)->reset();
    }

    /**
     * Each test uses its own REMOTE_ADDR so the per-IP rate limiter never collides between tests.
     *
     * @param array<string, string> $overrides raw anti-bot fields to override (register_ts, register_hp)
     */
    private function submitRegistration(string $email, string $ip, array $overrides = []): void
    {
        $crawler = $this->client->request('GET', '/register', [], [], ['REMOTE_ADDR' => $ip]);
        $form = $crawler->filter('form')->form();
        $form['registration[nickname]'] = strstr($email, '@', true);
        $form['registration[email]'] = $email;
        $form['registration[hash]'] = 'SuperSecret123';
        $form['registration[passwordConfirm]'] = 'SuperSecret123';
        $form['registration[claimsMembership]'] = '0';
        // Pretend the page was rendered 10 seconds ago, past the bot timing trap.
        $form['register_ts'] = (string) (time() - 10);
        foreach ($overrides as $field => $value) {
            $form[$field] = $value;
        }

        $this->client->submit($form, [], ['REMOTE_ADDR' => $ip]);
    }

    private function findUserByEmail(string $email): ?\App\Entity\User
    {
        return $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
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
