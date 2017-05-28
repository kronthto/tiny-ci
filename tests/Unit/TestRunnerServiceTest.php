<?php

namespace Tests\Unit;

use App\Commit;
use App\Services\GithubStatusService;
use App\Services\TestProcess;
use App\Services\TestRunnerService;
use ReflectionClass;
use Tests\TestCase;

class TestRunnerServiceTest extends TestCase
{
    /**
     * Test that the finalize method does all we expect from it:
     * Send to Github, set and save the joblog.
     */
    public function testFinalize()
    {
        $commit = new Commit();
        $commit->hash = 'beefbeef';
        $commit->id = 5;
        /** @var \Mockery\MockInterface|Commit $commit */
        $commit = \Mockery::instanceMock($commit);
        $commit->shouldReceive('save')->once();
        $commit->shouldReceive('buildUrl')->andReturn('http://domain.de/buildLog/5?hash=highlyconfidential');

        /** @var \Mockery\MockInterface|GithubStatusService $githubMock */
        $githubMock = \Mockery::mock(GithubStatusService::class);
        $githubMock->shouldReceive('postStatus')->with($commit, 'success', 'It works',
            'http://domain.de/buildLog/5?hash=highlyconfidential')->once();
        $this->app->instance(GithubStatusService::class, $githubMock);

        $service = app(TestRunnerService::class);
        $class = new ReflectionClass($service);
        $method = $class->getMethod('finalize');
        $method->setAccessible(true);

        /** @var TestProcess|\Mockery\MockInterface $testProcess */
        $testProcess = \Mockery::mock(TestProcess::class);
        $testProcess->shouldReceive('getLog')->andReturn(['line 1', 'line 2']);

        $method->invoke($service, $commit, $testProcess, 'success', 'It works');

        $this->assertEquals('line 1'.PHP_EOL.'line 2', $commit->joblog);
    }
}
