<?php

namespace App\Exceptions;

use Exception;

class CommandFailedException extends Exception
{
    /** @var string */
    protected $command;
    /** @var array|string[] */
    protected $output;

    public function __construct(string $command, array $output, int $code)
    {
        parent::__construct('An exec command failed', $code);

        $this->command = $command;
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return array|string[]
     */
    public function getOutput()
    {
        return $this->output;
    }
}
