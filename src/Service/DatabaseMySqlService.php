<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Database;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class DatabaseMySqlService
{
    private ParameterBagInterface $parameterBag;

    private BashScriptService $bashScriptService;

    private string $username;

    private string $password;

    public function __construct(
        ParameterBagInterface $parameterBag,
        BashScriptService $bashScriptService
    ) {
        $this->parameterBag = $parameterBag;
        $this->bashScriptService = $bashScriptService;

        $this->setMySQLCredentials();
    }

    public function createDatabase(Database $database): void
    {
        $command = sprintf('CREATE DATABASE %s;', $database->getName());

        $this->runMySQLCommand($command);
    }

    public function dropDatabase(Database $database): void
    {
        $command = sprintf('DROP DATABASE %s;', $database->getName());

        $this->runMySQLCommand($command);
    }

    public function userExists(Database $database): bool
    {
        $command = sprintf("SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user='%s');", $database->getUsername());

        $return = (int) $this->runMySQLCommandWithReturn($command);

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

        $this->runMySQLCommand($command);
    }

    public function dropUser(Database $database): void
    {
        $command = sprintf(
            "DROP USER '%s'@'%s';",
            $database->getUsername(),
            $database->getPermission(),
        );

        $this->runMySQLCommand($command);
    }

    public function grantAllPrivileges(Database $database): void
    {
        $command = sprintf(
            "GRANT ALL PRIVILEGES ON %s.* TO '%s'@'%s';",
            $database->getName(),
            $database->getUsername(),
            $database->getPermission()
        );

        $this->runMySQLCommand($command);
    }

    public function flushPrivileges(): void
    {
        $command = 'FLUSH PRIVILEGES;';

        $this->runMySQLCommand($command);
    }

    private function runMySQLCommand(string $commandLine): void
    {
        $command = sprintf(
            'sudo mysql -u %s -p%s -e "%s"',
            $this->username,
            $this->password,
            escapeshellarg($commandLine)
        );

        $this->bashScriptService->runCommandLineWithoutReturn($command);
    }

    private function runMySQLCommandWithReturn(string $commandLine): string
    {
        $command = sprintf(
            'sudo mysql -u %s -p%s -se "%s"',
            $this->username,
            $this->password,
            escapeshellarg($commandLine)
        );

        return $this->bashScriptService->runCommandLineWithReturn($command);
    }

    private function setMySQLCredentials()
    {
        $mysql = $this->parameterBag->get('mysql');

        $this->username = $mysql['user'] ?? '';
        $this->password = $mysql['password'] ?? '';
    }
}
