<?php

namespace App\Http\Controllers;

use App\Commit;
use App\Jobs\TestCommit;
use App\Project;
use App\Services\GithubStatusService;
use Illuminate\Http\Request;

class HookController extends Controller
{
    /** @var GithubStatusService */
    protected $githubStatusService;

    /**
     * HookController constructor.
     *
     * @param GithubStatusService $githubStatusService
     */
    public function __construct(GithubStatusService $githubStatusService)
    {
        $this->githubStatusService = $githubStatusService;
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
        $payload = $request->json()->all();

        if (!isset($payload['after'])) {
            return response('Need a revision to check', 400);
        }

        $pushRevision = $payload['after'];

        if ($payload['deleted'] === true || $pushRevision === '0000000000000000000000000000000000000000') {
            return response('Doing nothing on delete / 000.. events');
        }

        $project = Project::findBySlug($slug);

        $commit = Commit::query()
            ->where('project_id', '=', $project->id)
            ->where('hash', '=', $pushRevision)
            ->first();

        if ($commit) {
            return response('Already checked this revision');
        }

        /** @var Commit $commit */
        $commit = $project->commits()->create([
            'hash' => $pushRevision,
            'task' => Commit::TASK_PUSH,
        ]);

        $this->githubStatusService->postStatus($commit, 'pending', null);

        $this->dispatch(new TestCommit($commit));

        return response('Job queued', 202);
    }
}
