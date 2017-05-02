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
        $payload = $request->json();

        $project = Project::findBySlug($slug);

        $pushRevision = $payload->ref;

        $commit = Commit::query()
            ->where('project_id', '=', $project->id)
            ->where('hash', '=', $pushRevision)
            ->first();

        if ($commit) {
            return response('Already checked this revision');
        }

        $this->githubStatusService->postStatus($project, $pushRevision, 'pending', null);

        /** @var Commit $commit */
        $commit = $project->commits()->create([
            'hash' => $pushRevision,
        ]);

        $this->dispatch(new TestCommit($commit));

        return response('Job queued', 202);
    }
}
