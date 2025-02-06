<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ConfigFileDto;
use App\Entity\Database;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

final class DatabaseMySqlService
{
    private ParameterBagInterface $parameterBag;

    private BashScriptService $bashScriptService;

    private string $username;

    private string $password;

    private Filesystem $filesystem;

    public function __construct(
        ParameterBagInterface $parameterBag,
        BashScriptService $bashScriptService
    ) {
        $this->parameterBag = $parameterBag;
        $this->bashScriptService = $bashScriptService;
        $this->filesystem = new Filesystem();

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
        $command = sprintf(
            'SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user=%s);',
            escapeshellarg($database->getUsername())
        );

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
            $commandLine
        );

        return $this->bashScriptService->runCommandLineWithReturn($command);
    }

    private function setMySQLCredentials()
    {
        $mysql = $this->parameterBag->get('mysql');

        $this->username = $mysql['user'] ?? '';
        $this->password = $mysql['password'] ?? '';
    }

    public function isRunning(): bool
    {
        $command = ['systemctl', 'is-active', 'mysql'];

        $output = $this->bashScriptService->runCommandWithReturn($command, null, false);

        $status = preg_replace("/[^a-zA-Z]+/", '', $output);

        return $status === 'active';
    }

    public function getContentFile(string $fileName): ConfigFileDto
    {
        if ($this->filesystem->exists($fileName)) {
            $command = ['sudo', 'cat', $fileName];
            $return = $this->bashScriptService->runCommandWithReturn($command);
        }

        return ConfigFileDto::create()
            ->setName($fileName)
            ->setContent($return ?? '');
    }

    public function changeStatus(string $action): void
    {
        if ($action == 'restart' && $this->isRunning() === false) {
            return;
        }

        $command = ['sudo', 'service', 'mysql', $action];

        $this->bashScriptService->runCommandWithoutReturn($command);
    }
}
