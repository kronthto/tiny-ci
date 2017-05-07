<?php

namespace App\Services;

use App\Commit;

class TestRunnerService
{
    const CONFIG_FILE = 'tinyci.json';

    /** @var GithubStatusService */
    protected $githubStatusService;

    /**
     * TestRunnerService constructor.
     *
     * @param GithubStatusService $githubStatusService
     */
    public function __construct(GithubStatusService $githubStatusService)
    {
        $this->githubStatusService = $githubStatusService;
    }

    /**
     * Runs the test process.
     *
     * @param Commit $commit
     */
    public function runTestsForCommit(Commit $commit)
    {
        $project = $commit->project;

        chdir(storage_path('app/repos/'.$project->slug));

        $gitExec = config('app.gitexec');
        $prepCommands = [
            "export DEBIAN_FRONTEND=noninteractive",
            "$gitExec fetch",
            "$gitExec reset --hard",
            "$gitExec checkout -f ".$commit->hash,
        ];
        foreach ($prepCommands as $prepCmd) {
            exec($prepCmd, $outp, $retCode);
            if ($retCode !== 0) {
                logger()->error('Error executing preparation cmd', [
                    'command' => $prepCmd,
                    'commit' => $commit->toArray(),
                    'project' => $project->toArray(),
                    'output' => $outp,
                ]);

                $this->githubStatusService->postStatus(
                    $project,
                    $commit->hash,
                    'failure',
                    'Error executing preparation command'
                );

                return;
            }
        }

        $config = null;
        try {
            $config = \GuzzleHttp\json_decode(file_get_contents(static::CONFIG_FILE));
        } catch (\Exception $e) {
            $this->githubStatusService->postStatus(
                $project,
                $commit->hash,
                'failure',
                'Error reading '.static::CONFIG_FILE
            );

            $commit->passing = false;
            $commit->save();

            return;
        }

        foreach ($config->before as $i => $beforeCmd) {
            exec($beforeCmd, $outp, $retCode);
            if ($retCode !== 0) {
                logger()->error('Error executing before cmd '.$i, [
                    'config' => $config,
                    'commit' => $commit->toArray(),
                    'project' => $project->toArray(),
                    'output' => $outp,
                ]);

                $this->githubStatusService->postStatus(
                    $project,
                    $commit->hash,
                    'failure',
                    'Error executing before commands'
                );

                $commit->passing = false;
                $commit->save();

                return;
            }
        }

        exec($config->script, $output, $returnCode);

        if ($returnCode !== 0) {
            $message = end($output);

            $commit->passing = false;
            $commit->save();

            $this->githubStatusService->postStatus($project, $commit->hash, 'error', $message);
        } else {
            $message = null;
            foreach ($output as $line) {
                $timeConsumed = preg_match('/^Time:\s+(.+),/', $line, $matches);
                if ($timeConsumed === 1) {
                    $message = 'Passed in '.$matches[1];
                    break;
                }
            }
            if (is_null($message)) {
                $message = end($output);
            }

            $commit->passing = true;
            $commit->save();

            $this->githubStatusService->postStatus($project, $commit->hash, 'success', $message);
        }
    }
}
