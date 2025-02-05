<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Database;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class DatabaseMySqlService
{
    public function createDatabase(Database $database): void
    {
        $command = sprintf('CREATE DATABASE %s;', $database->getName());

        $this->runCommand($command);
    }

    public function dropDatabase(Database $database): void
    {
        $command = sprintf('DROP DATABASE %s;', $database->getName());

        $this->runCommand($command);
    }

    public function createUser(Database $database): void
    {
        $command = sprintf(
            "CREATE USER '%s'@'%s' IDENTIFIED BY '%s';",
            $database->getUsername(),
            $database->getPermission(),
            $database->getPassword()
        );

        $this->runCommand($command);
    }

    public function dropUser(Database $database): void
    {
        $command = sprintf(
            "DROP USER '%s'@'%s';",
            $database->getUsername(),
            $database->getPermission(),
        );

        $this->runCommand($command);
    }

    public function grantAllPrivileges(Database $database): void
    {
        $command = sprintf(
            "GRANT ALL PRIVILEGES ON %s.* TO '%s'@'%s';",
            $database->getName(),
            $database->getUsername(),
            $database->getPermission()
        );

        $this->runCommand($command);
    }

    public function flushPrivileges(): void
    {
        $command = 'FLUSH PRIVILEGES;';

        $this->runCommand($command);
    }

    private function runCommand(string $command): void
    {
        $cmdCommand = sprintf('sudo mysql -u root -proot2017 -e "%s"', $command);

        $process = Process::fromShellCommandline($cmdCommand);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function runCommandWithReturn(string $command): string
    {
        $cmdCommand = sprintf('sudo mysql -u root -proot2017 -se "%s"', $command);

        $process = Process::fromShellCommandline($cmdCommand);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public function userExists(Database $database): bool
    {
        $command = sprintf("SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user='%s');", $database->getUsername());

        $return = (int) $this->runCommandWithReturn($command);

        return $return === 1;
    }
}