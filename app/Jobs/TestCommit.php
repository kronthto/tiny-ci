<?php

namespace App\Jobs;

use App\Commit;
use App\Services\GithubStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestCommit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var Commit */
    protected $commit;

    /**
     * Create a new job instance.
     *
     * @param Commit $commit
     */
    public function __construct(Commit $commit)
    {
        $this->commit = $commit;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        sleep(15);
        app(GithubStatusService::class)->postStatus(
            $this->commit->project,
            $this->commit->hash,
            'success',
            'TEST ONLY'
        );
    }
}
