<?php

namespace App\Tests\Controller;

use App\Tests\Support\AppWebTestCase;

class HomeControllerTest extends AppWebTestCase
{
    public function testHomepageIsReachableAnonymously(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }
}
