<?php

namespace Tests\Feature;

use Illuminate\Foundation\Application;
use Tests\TestCase;

class ApplicationDoesNotCrashTest extends TestCase
{
    /**
     * A basic test.
     */
    public function testResponseIsSuccessful()
    {
        $this->assertInstanceOf(Application::class, $this->app);

        /** @var \Illuminate\Foundation\Testing\TestResponse|\Illuminate\Http\Response $response */
        $response = $this->get('/');

        $this->assertGreaterThanOrEqual(200, $response->getStatusCode());
        $this->assertLessThan(500, $response->getStatusCode());
    }
}
