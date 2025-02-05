<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class BashScriptService
{
    public function runCommandWithReturn(array $command, ?string $workingDirectory = null): string
    {
        $process = new Process($command);

        if ($workingDirectory !== null) {
            $process->setWorkingDirectory($workingDirectory);
        }

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public function runCommandWithoutReturn(array $command, ?string $workingDirectory = null): void
    {
        $process = new Process($command);

        if ($workingDirectory !== null) {
            $process->setWorkingDirectory($workingDirectory);
        }

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function runCommandLineWithoutReturn(string $command, ?string $workingDirectory = null)
    {
        $process = Process::fromShellCommandline($command);

        if ($workingDirectory !== null) {
            $process->setWorkingDirectory($workingDirectory);
        }

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function runCommandWithContent(): void
    {
    }
}