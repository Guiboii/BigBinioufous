<?php

namespace App\Tests\Controller;

use App\Tests\Support\AppWebTestCase;

class LocaleControllerTest extends AppWebTestCase
{
    public function testSwitchingLocaleRedirectsToInternalReferer(): void
    {
        $this->client->request('GET', '/locale/en', [], [], ['HTTP_REFERER' => 'http://localhost/contact']);

        $this->assertResponseRedirects('http://localhost/contact');
        $this->assertSame('en', $this->client->getRequest()->getSession()->get('_locale'));
    }

    public function testLocaleOutsideAllowedListIsNotFound(): void
    {
        $this->client->request('GET', '/locale/xx');

        $this->assertResponseStatusCodeSame(404);
    }
}
