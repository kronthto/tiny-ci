<?php

namespace Tests\Feature;

use App\Commit;
use App\Jobs\TestCommit;
use App\Services\TestRunnerService;
use Tests\TestCase;

class CommitQueueItemTest extends TestCase
{
    /**
     * It should just pass on the commit to the TestRunnerService.
     */
    public function testItPassesOnToTheTestRunnerService()
    {
        $commit = new Commit();

        $service = \Mockery::mock(TestRunnerService::class);
        $service->shouldReceive('runTestsForCommit')->with($commit)->once();

        $this->app->instance(TestRunnerService::class, $service);

        $queueItem = new TestCommit($commit);
        $queueItem->handle();
    }
}
