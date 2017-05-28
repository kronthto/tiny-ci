<?php

namespace Tests\Unit;

use App\Services\GitService;
use Tests\TestCase;

class GitServiceTest extends TestCase
{
    /**
     * Test that it prefixes commands correctly.
     */
    public function testBuildCommand()
    {
        $service = new GitService();
        $this->assertEquals('git stash', $service->buildCommand('stash'));

        \Config::set('app.gitexec', '/usr/bin/git');
        $service = new GitService();
        $this->assertEquals('/usr/bin/git fetch origin', $service->buildCommand('fetch origin'));
    }
}
