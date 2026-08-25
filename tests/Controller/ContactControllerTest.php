<?php

namespace App\Tests\Controller;

use App\Tests\Support\AppWebTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

class ContactControllerTest extends AppWebTestCase
{
    use MailerAssertionsTrait;

    public function testContactPageIsReachableAnonymously(): void
    {
        $this->client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
    }

    public function testSubmitWithInvalidCsrfTokenIsRejected(): void
    {
        $this->client->request('POST', '/contact', [
            '_token' => 'not-a-real-token',
            'name' => 'Marine',
            'email' => 'marine@example.test',
            'message' => 'Bonjour',
            'contact_ts' => time() - 10,
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_token"}', (string) $this->client->getResponse()->getContent());
    }

    public function testSubmitTooFastAfterPageLoadIsSilentlyTreatedAsBot(): void
    {
        // Distinct REMOTE_ADDR per test: the contact rate limiter (1/minute/IP) is backed by a cache pool that
        // isn't reset by the fresh-schema setUp(), so sharing an IP across test methods would collide between them.
        $this->client->request('POST', '/contact', [
            '_token' => $this->csrfToken('contact'),
            'name' => 'Marine',
            'email' => 'marine@example.test',
            'message' => 'Bonjour',
            'contact_ts' => time(),
        ], [], ['REMOTE_ADDR' => '10.0.0.1']);

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"success":true}', (string) $this->client->getResponse()->getContent());
        $this->assertEmailCount(0);
    }

    public function testValidSubmissionSendsAnEmail(): void
    {
        $this->client->request('POST', '/contact', [
            '_token' => $this->csrfToken('contact'),
            'name' => 'Marine',
            'email' => 'marine@example.test',
            'message' => 'Bonjour, une question sur la fanfare.',
            'contact_ts' => time() - 10,
        ], [], ['REMOTE_ADDR' => '10.0.0.2']);

        $this->assertResponseIsSuccessful();
        $this->assertEmailCount(1);
    }
}
