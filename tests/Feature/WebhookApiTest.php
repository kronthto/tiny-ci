<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class WebhookApiTest extends TestCase
{
    use WithoutMiddleware;

    /**
     * A request payload should have an after field.
     */
    public function testItRejectsResponsesWithoutRevision()
    {
        /** @var \Illuminate\Foundation\Testing\TestResponse|\Illuminate\Http\Response $response */
        $response = $this->postJson('/api/hook/testproject', ['foo' => 'bar']);

        $response->assertStatus(400);
    }

    /**
     * We are only interested in pushes with new commits.
     */
    public function testItDoesNothingOnDeletes()
    {
        /** @var \Illuminate\Foundation\Testing\TestResponse|\Illuminate\Http\Response $response */
        $responseDeleted = $this->postJson('/api/hook/testproject', ['deleted' => true, 'after' => 'blub']);
        $responseDeleted->assertSeeText('nothing');

        /** @var \Illuminate\Foundation\Testing\TestResponse|\Illuminate\Http\Response $response */
        $responseZeroZero = $this->postJson(
            '/api/hook/testproject',
            ['deleted' => false, 'after' => '0000000000000000000000000000000000000000']
        );
        $responseZeroZero->assertSeeText('nothing');
    }
}
