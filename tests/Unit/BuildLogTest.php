<?php

namespace Tests\Unit;

use App\Commit;
use Tests\TestCase;

class BuildLogTest extends TestCase
{
    /**
     * The hashes should not always be the same.
     */
    public function testSecretKeyDiffersForDifferentCommits()
    {
        $commit1 = new Commit();
        $commit1->id = 1;
        $commit2 = new Commit(['id' => 5]);
        $commit2->id = 2;

        $this->assertNotEquals($commit1->getSecretToken(), $commit2->getSecretToken());
    }

    /**
     * Assert that the generated URLs match the expected format.
     */
    public function testBuildUrl()
    {
        /** @var Commit|\Mockery\MockInterface $commit */
        $commit = \Mockery::mock(Commit::class)->makePartial();
        $commit->shouldReceive('getSecretToken')->andReturn('secrettokenabab');
        $commit->id = 3;

        $this->assertStringEndsWith('3?token=secrettokenabab', $commit->buildUrl());
    }
}
