<?php

namespace Tests\Feature;

use App\Commit;
use App\Http\Controllers\HookController;
use App\Jobs\TestCommit;
use App\Project;
use App\Services\GithubStatusService;
use App\Services\ProjectResolver;
use App\Services\TestRunnerService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Queue;
use ReflectionObject;
use Tests\TestCase;

class WebhookApiTest extends TestCase
{
    use WithoutMiddleware;

    /** @var Project|null */
    protected $fakeProjectResolve = null;

    protected function setUp()
    {
        parent::setUp();

        // Patch resolving project slug to work without DB.
        /** @var ProjectResolver|\Mockery\MockInterface $resolver */
        $resolver = \Mockery::mock(ProjectResolver::class);
        $resolver->shouldReceive('bySlug')->andReturnUsing(function (string $slug): Project {
            if (!is_null($this->fakeProjectResolve)) {
                return $this->fakeProjectResolve;
            }

            return new Project([
                'slug' => $slug,
            ]);
        });
        $this->app->instance(ProjectResolver::class, $resolver);
    }

    /**
     * A 501 should be returned if the event can't be processed.
     */
    public function testItRejectsUnknownEvents()
    {
        /** @var \Illuminate\Foundation\Testing\TestResponse|\Illuminate\Http\Response $response */
        $response = $this->postJson('/api/hook/testproject', [], ['X-GitHub-Event' => 'eatingicecreme']);

        $response->assertStatus(501);
    }

    /**
     * A request payload should have an after field.
     */
    public function testItRejectsPushesWithoutRevision()
    {
        /** @var \Illuminate\Foundation\Testing\TestResponse|\Illuminate\Http\Response $response */
        $response = $this->postJson('/api/hook/testproject', ['foo' => 'bar'], ['X-GitHub-Event' => 'push']);

        $response->assertStatus(400);
    }

    /**
     * We are only interested in pushes with new commits.
     */
    public function testItDoesNothingOnDeletes()
    {
        /** @var \Illuminate\Foundation\Testing\TestResponse|\Illuminate\Http\Response $response */
        $responseDeleted = $this->postJson('/api/hook/testproject', ['deleted' => true, 'after' => 'blub'],
            ['X-GitHub-Event' => 'push']);
        $responseDeleted->assertSeeText('nothing');

        /** @var \Illuminate\Foundation\Testing\TestResponse|\Illuminate\Http\Response $responseZeroZero */
        $responseZeroZero = $this->postJson(
            '/api/hook/testproject',
            ['deleted' => false, 'after' => '0000000000000000000000000000000000000000'],
            ['X-GitHub-Event' => 'push']
        );
        $responseZeroZero->assertSeeText('nothing');
    }

    /**
     * A push should queue a check job.
     * A already checked commit should not.
     */
    public function testItAcceptsPushes()
    {
        $project = new Project([
            'slug' => 'testproject',
        ]);
        $newCommit = new Commit([
            'hash' => '1000000000000000000000000000000000000000',
            'task' => 'push',
            'project' => $project,
        ]);
        /** @var \Mockery\MockInterface|HasMany $commitsRelation */
        $commitsRelation = \Mockery::mock(HasMany::class);
        $commitsRelation->shouldReceive('create')->with([
            'hash' => '1000000000000000000000000000000000000000',
            'task' => 'push',
        ])->once()->andReturn($newCommit);
        /** @var \Mockery\MockInterface|Project $project */
        $project = \Mockery::instanceMock($project);
        $project->shouldReceive('commits')->andReturn($commitsRelation);
        $this->fakeProjectResolve = $project;

        /** @var \Mockery\MockInterface|TestRunnerService $testRunnerServiceMock */
        $testRunnerServiceMock = \Mockery::mock(TestRunnerService::class);
        $testRunnerServiceMock->shouldReceive('hasCheckedCommit')->with(Project::class,
            '1000000000000000000000000000000000000000')
            ->andReturn(false);
        $testRunnerServiceMock->shouldReceive('hasCheckedCommit')->with(Project::class,
            '2000000000000000000000000000000000000000')
            ->andReturn(true);
        $this->app->instance(TestRunnerService::class, $testRunnerServiceMock);

        /** @var \Mockery\MockInterface|GithubStatusService $githubMock */
        $githubMock = \Mockery::mock(GithubStatusService::class);
        $githubMock->shouldReceive('postStatus')->with($newCommit, 'pending', null)->once();
        $this->app->instance(GithubStatusService::class, $githubMock);

        // Forget the instance to have it re-build with the service mock
        $this->app->forgetInstance(HookController::class);

        Queue::fake();

        /** @var \Illuminate\Foundation\Testing\TestResponse|\Illuminate\Http\Response $response */
        $response = $this->postJson('/api/hook/testproject', ['after' => '1000000000000000000000000000000000000000'],
            ['X-GitHub-Event' => 'push']);

        $response->assertStatus(202);
        Queue::assertPushed(TestCommit::class, function (TestCommit $job) {
            $r = new ReflectionObject($job);
            $p = $r->getProperty('commit');
            $p->setAccessible(true);
            /** @var Commit $commit */
            $commit = $p->getValue($job);

            return $commit->project->slug === 'testproject' && $commit->hash === '1000000000000000000000000000000000000000' && $commit->task === Commit::TASK_PUSH;
        });

        /** @var \Illuminate\Foundation\Testing\TestResponse|\Illuminate\Http\Response $response */
        $response = $this->postJson('/api/hook/testproject', ['after' => '2000000000000000000000000000000000000000'],
            ['X-GitHub-Event' => 'push']);
        $response->assertSeeText('Already');
        Queue::assertNotPushed(TestCommit::class, function (TestCommit $job) {
            $r = new ReflectionObject($job);
            $p = $r->getProperty('commit');
            $p->setAccessible(true);
            /** @var Commit $commit */
            $commit = $p->getValue($job);

            return $commit->hash === '2000000000000000000000000000000000000000';
        });
    }
}
