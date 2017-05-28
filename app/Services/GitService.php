<?php

namespace App\Services;

class GitService
{
    /** @var string */
    protected $git;

    /**
     * GitService constructor.
     */
    public function __construct()
    {
        $this->git = config('app.gitexec');
    }

    /**
     * Builds the exec command string.
     *
     * @param string $op
     *
     * @return string
     */
    public function buildCommand(string $op): string
    {
        return $this->git.' '.$op;
    }

    /**
     * Returns the commit hash to a given ref.
     *
     * @param string $refspec
     *
     * @return string
     */
    public function fetchRefCommit(string $refspec): string
    {
        return exec($this->buildCommand('rev-list -n1 '.$refspec));
    }

    /**
     * Returns the HEAD commit hash of the PR branch.
     *
     * @param int $number
     *
     * @return string
     */
    public function getPRHead(int $number)
    {
        return $this->fetchRefCommit('origin/pr/'.$number.'/head');
    }

    /**
     * Returns the commit hash that would be the merge result.
     *
     * @param int $number
     *
     * @return string
     */
    public function getPRMerge(int $number)
    {
        return $this->fetchRefCommit('origin/pr/'.$number.'/head');
    }

    /**
     * Fetches including the Github PR refs.
     */
    public function fetchPRs()
    {
        exec($this->buildCommand('fetch origin +refs/pull/*:refs/remotes/origin/pr/*'));
    }
}
