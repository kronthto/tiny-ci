<?php

namespace App\Services;

use App\Commit;
use App\Exceptions\CommandFailedException;
use App\Project;

class TestRunnerService
{
    const CONFIG_FILE = 'tinyci.json';

    /** @var GithubStatusService */
    protected $githubStatusService;
    /** @var GitService */
    protected $gitService;

    /**
     * TestRunnerService constructor.
     *
     * @param GithubStatusService $githubStatusService
     * @param GitService          $gitService
     */
    public function __construct(GithubStatusService $githubStatusService, GitService $gitService)
    {
        $this->githubStatusService = $githubStatusService;
        $this->gitService = $gitService;
    }

    /**
     * Determine whether a commit has already been checked.
     *
     * @param Project $project
     * @param string  $hash
     *
     * @return bool
     */
    public function hasCheckedCommit(Project $project, string $hash): bool
    {
        $commit = Commit::query()
            ->where('project_id', '=', $project->id)
            ->where('hash', '=', $hash)
            ->first();

        return (bool) $commit;
    }

    protected function finalize(Commit $commit, TestProcess $testProcess, string $state, string $message)
    {
        $this->githubStatusService->postStatus(
            $commit,
            $state,
            $message,
            $commit->buildUrl()
        );

        $commit->joblog = implode(PHP_EOL, $testProcess->getLog());
        $commit->save();
    }

    /**
     * Runs the test process.
     *
     * @param Commit $commit
     */
    public function runTestsForCommit(Commit $commit)
    {
        $project = $commit->project;
        $jobProcess = new TestProcess();

        chdir(storage_path('app/repos/'.$project->slug));

        putenv('DEBIAN_FRONTEND=noninteractive');
        $prepCommands = [
            'export DEBIAN_FRONTEND=noninteractive',
            $this->gitService->buildCommand('fetch'),
            $this->gitService->buildCommand('reset --hard'),
            $this->gitService->buildCommand('checkout -f '.$commit->hash),
        ];
        foreach ($prepCommands as $prepCmd) {
            try {
                $jobProcess->exec($prepCmd);
            } catch (CommandFailedException $e) {
                $this->finalize($commit, $jobProcess, 'failure', 'Error executing preparation cmd');

                return;
            }
        }

        $config = null;
        try {
            $config = \GuzzleHttp\json_decode(file_get_contents(static::CONFIG_FILE));
        } catch (\Exception $e) {
            $commit->passing = false;
            $this->finalize($commit, $jobProcess, 'failure', 'Error reading '.static::CONFIG_FILE);

            return;
        }

        foreach ($config->before as $i => $beforeCmd) {
            try {
                $jobProcess->exec($beforeCmd);
            } catch (CommandFailedException $e) {
                $commit->passing = false;
                $this->finalize($commit, $jobProcess, 'failure', 'Error executing before commands');

                return;
            }
        }

        $cmdOut = null;
        try {
            $cmdOut = $jobProcess->exec($config->script);
        } catch (CommandFailedException $e) {
            $cmdOut = $e->getOutput();
            $message = end($cmdOut);

            $commit->passing = false;
            $this->finalize($commit, $jobProcess, 'error', $message);

            return;
        }

        $message = null;
        foreach ($cmdOut as $line) {
            $timeConsumed = preg_match('/^Time:\s+(.+),/', $line, $matches);
            if ($timeConsumed === 1) {
                $message = 'Passed in '.$matches[1];
                break;
            }
        }
        if (is_null($message)) {
            $message = end($cmdOut);
        }

        $commit->passing = true;
        $this->finalize($commit, $jobProcess, 'success', $message);
    }
}
