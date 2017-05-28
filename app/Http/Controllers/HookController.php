<?php

namespace App\Http\Controllers;

use App\Commit;
use App\Jobs\TestCommit;
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

        switch ($request->header('X-GitHub-Event')) {
            case 'push':
                $commit = $this->handlePushEvent($payload);
                $task = Commit::TASK_PUSH;
                break;

            default:
                $commit = response('Event handler not implemented', 501);
        }

        if ($commit instanceof Response) {
            return $commit;
        }
        // If it's not a response we assume it is a string representing the resolved revision
        if ($this->testRunnerService->hasCheckedCommit($project, $commit)) {
            return response('Already checked this revision');
        }

        /** @var Commit $commit */
        /** @noinspection PhpUndefinedVariableInspection */
        $commit = $project->commits()->create([
            'hash' => $commit,
            'task' => $task,
        ]);

        $this->githubStatusService->postStatus($commit, 'pending', null);

        $this->dispatch(new TestCommit($commit));

        return response('Job queued', 202);
    }

    /**
     * Returns the commit to be checked for the given push.
     *
     * @param $payload
     *
     * @return Response|string
     */
    protected function handlePushEvent($payload)
    {
        if (!isset($payload['after'])) {
            return response('Need a revision to check', 400);
        }

        $pushRevision = $payload['after'];

        if ((isset($payload['deleted']) && $payload['deleted'] === true) || $pushRevision === '0000000000000000000000000000000000000000') {
            return response('Doing nothing on delete / 000.. events');
        }

        return $pushRevision;
    }
}
