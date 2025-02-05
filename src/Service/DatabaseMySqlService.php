<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Database;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class DatabaseMySqlService
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

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

    public function userExists(Database $database): bool
    {
        $command = sprintf("SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user='%s');", $database->getUsername());

        $return = (int) $this->runCommandWithReturn($command);

        return $return === 1;
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
        $mysql = $this->parameterBag->get('mysql');

        $cmdCommand = sprintf(
            'sudo mysql -u %s -p%s -e "%s"',
            $mysql['user'],
            $mysql['password'],
            $command
        );

        $process = Process::fromShellCommandline($cmdCommand);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function runCommandWithReturn(string $command): string
    {
        $mysql = $this->parameterBag->get('mysql');

        $cmdCommand = sprintf(
            'sudo mysql -u %s -p%s -se "%s"',
            $mysql['user'],
            $mysql['password'],
            $command
        );

        $process = Process::fromShellCommandline($cmdCommand);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
}