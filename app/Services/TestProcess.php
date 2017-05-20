<?php

namespace App\Services;

use App\Exceptions\CommandFailedException;

class TestProcess
{
    /** @var string[]|array */
    protected $log = [];

    /**
     * exec wrapper that checks return code and writes a joblog.
     *
     * @param string $command
     *
     * @return array|string[]
     *
     * @throws CommandFailedException
     */
    public function exec(string $command)
    {
        $this->log[] = '$ '.$command;

        exec($command, $outp, $retCode);

        $this->log = array_merge($this->log, $outp);

        if ($retCode !== 0) {
            $this->log[] = 'Terminated with exit code '.$retCode;

            throw new CommandFailedException($command, $outp, $retCode);
        }

        return $outp;
    }

    /**
     * @return array|string[]
     */
    public function getLog()
    {
        return $this->log;
    }
}
