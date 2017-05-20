<?php

namespace Tests\Unit;

use App\Exceptions\CommandFailedException;
use App\Services\TestProcess;
use Tests\TestCase;

class TestProcessTest extends TestCase
{
    /**
     * We execute some commands and expect a log.
     */
    public function testHappyPath()
    {
        $testProcess = new TestProcess();
        $testProcess->exec('echo foo');
        $testProcess->exec('echo bar');

        $this->assertEquals([
            '$ echo foo',
            'foo',
            '$ echo bar',
            'bar',
        ], $testProcess->getLog());
    }

    /**
     * And some point, a command fails. An exception should be raised.
     */
    public function testFailing()
    {
        $testProcess = new TestProcess();
        $testProcess->exec('echo foo');

        $exception = null;

        try {
            $testProcess->exec('thiscmdshouldnotexistandthereforereturnanonnullexitcode');
        } catch (CommandFailedException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertNotEquals(0, $exception->getCode());

        $log = $testProcess->getLog();

        $this->assertGreaterThanOrEqual(4, $log);
        $this->assertEquals('$ echo foo', $log[0]);
        $this->assertStringStartsWith('Terminated with exit code ', end($log));
    }
}
