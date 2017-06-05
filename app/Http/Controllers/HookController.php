<?php

namespace App\Http\Controllers;

use App\Commit;
use App\Jobs\TestCommit;
use App\Project;
use App\Services\GithubStatusService;
use App\Services\ProjectResolver;
use App\Services\TestRunnerService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HookController extends Controller
{
    /** @var GithubStatusService */
    protected $githubStatusService;
    /** @var TestRunnerService */
    protected $testRunnerService;
    /** @var ProjectResolver */
    protected $projectResolver;

    /** @var array|EvalJob[] */
    protected $evalJobs;

    /**
     * HookController constructor.
     *
     * @param GithubStatusService $githubStatusService
     * @param TestRunnerService   $testRunnerService
     * @param ProjectResolver     $projectResolver
     */
    public function __construct(
        GithubStatusService $githubStatusService,
        TestRunnerService $testRunnerService,
        ProjectResolver $projectResolver
    ) {
        $this->githubStatusService = $githubStatusService;
        $this->testRunnerService = $testRunnerService;
        $this->projectResolver = $projectResolver;
    }

    /**
     * Receives the webhook payload.
     *
     * @param Request $request
     * @param string  $slug
     *
     * @return \Illuminate\Http\Response
     */
    public function takePayloadAction(Request $request, string $slug)
    {
        $project = $this->projectResolver->bySlug($slug);
        $payload = $request->json()->all();
        $this->evalJobs = [];

        switch ($request->header('X-GitHub-Event')) {
            case 'push':
                $error = $this->handlePushEvent($payload);
                break;

            default:
                $error = response('Event handler not implemented', 501);
        }

        if ($error) {
            return $error;
        }

        $queued = 0;
        foreach ($this->evalJobs as $commit) {
            if ($this->checkAndQueueJob($project, $commit)) {
                ++$queued;
            }
        }

        if ($queued == 0) {
            return response('Already checked everything for this event');
        }

        return response($queued.'/'.sizeof($this->evalJobs).' Jobs queued', 202);
    }

    /**
     * Check whether the commit has already been checked. If not, queue it.
     *
     * @param Project $project
     * @param EvalJob $job
     *
     * @return bool whether something has been queued
     */
    protected function checkAndQueueJob(Project $project, EvalJob $job): bool
    {
        if ($this->testRunnerService->hasCheckedCommit($project, $job->hash)) {
            return false;
        }

        /** @var Commit $commit */
        $commit = $project->commits()->create([
            'hash' => $job->hash,
            'task' => $job->task,
        ]);

        $this->githubStatusService->postStatus($commit, 'pending', null);

        $this->dispatch(new TestCommit($commit));

        return true;
    }

    /**
     * Extracts the commit-hash for the given push or returns an error response.
     *
     * @param $payload
     *
     * @return Response|null
     */
    protected function handlePushEvent($payload): ?Response
    {
        if (!isset($payload['after'])) {
            return response('Need a revision to check', 400);
        }

        $pushRevision = $payload['after'];

        if ((isset($payload['deleted']) && $payload['deleted'] === true) || $pushRevision === '0000000000000000000000000000000000000000') {
            return response('Doing nothing on delete / 000.. events');
        }

        $job = new EvalJob();
        $job->hash = $pushRevision;
        $job->task = Commit::TASK_PUSH;
        $this->evalJobs[] = $job;

        return null;
    }
}

class EvalJob
{
    public $hash;
    public $task;
}
