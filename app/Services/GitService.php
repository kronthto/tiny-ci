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
}
