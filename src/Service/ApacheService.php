<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Site;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class ApacheService
{
    protected ParameterBagInterface $parameterBag;

    protected ApacheVirtualHostFileService $apacheVirtualHostFileService;

    protected MkcertService $mkcertService;

    private string $apacheVirtualHostPath;

    private Filesystem $filesystem;

    public function __construct(
        ParameterBagInterface $parameterBag,
        ApacheVirtualHostFileService $apacheVirtualHostFileService,
        MkcertService $mkcertService

    ) {
        $this->parameterBag = $parameterBag;
        $this->apacheVirtualHostFileService = $apacheVirtualHostFileService;
        $this->mkcertService = $mkcertService;
        $this->apacheVirtualHostPath = $this->parameterBag->get('apacheVirtualHostPath');
        $this->filesystem = new Filesystem();
    }

    public function create(Site $site): void
    {
        $this->apacheVirtualHostFileService->create($site);
        $this->mkcertService->generate($site->getDomain());
        $this->enableSite($site->getDomainConf());
        $this->appendSiteInHosts($site->getDomain());
        $this->restartApache();

        $this->createFolder($site);
        $this->createFile($site);
        $this->setPermission($site);
    }

    public function delete(Site $site): void
    {
        $this->apacheVirtualHostFileService->delete($site);
        $this->mkcertService->delete($site->getDomain());
        $this->disableSite($site->getDomainConf());
        $this->restartApache();
    }

    private function enableSite(string $fileName): void
    {
        $process = new Process(['sudo', 'a2ensite', $fileName]);
        $process->setWorkingDirectory($this->apacheVirtualHostPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function disableSite(string $fileName): void
    {
        if (!$this->filesystem->exists($this->apacheVirtualHostPath . $fileName)) {
            return;
        }

        $process = new Process(['sudo', 'a2dissite', $fileName]);
        $process->setWorkingDirectory($this->apacheVirtualHostPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function restartApache(): void
    {
        $process = new Process(['sudo', 'service', 'apache2', 'reload']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function restartFpm(string $phpVersion): void
    {
        $phpFpmVersion = sprintf('php%s-fpm', $phpVersion);

        $process = new Process(['sudo', 'systemctl', 'restart', $phpFpmVersion]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function createFolder(Site $site): void
    {
        $process = new Process(['sudo', 'mkdir', '-p', $site->getDocumentRoot()]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function createFile(Site $site): void
    {
        $content = '<?php echo phpinfo();';

        $process = new Process([
            "sudo",
            "bash",
            "-c",
            "echo " . escapeshellarg($content) . " > " . escapeshellarg('index.php'),
        ]);
        $process->setWorkingDirectory($site->getDocumentRoot());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function appendSiteInHosts(string $domain): void
    {
        $domainRow = sprintf('127.0.0.1       %s', $domain);

        $command = sprintf('grep -qxF "%s" /etc/hosts || echo "%s" | sudo tee -a /etc/hosts', $domainRow, $domainRow);

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function getVirtualHostConf(Site $site): string
    {
        return $this->apacheVirtualHostFileService->get($site);
    }

    public function updateVirtualHostConf(Site $site, string $content): void
    {
        $this->apacheVirtualHostFileService->update($site, $content);
        $this->restartApache();
    }

    public function getUserIni(Site $site): string
    {
        $fileName = '.user.ini';

        if (!$this->filesystem->exists($site->getDocumentRoot() . DIRECTORY_SEPARATOR . $fileName)) {
            return '';
        }

        $process = new Process(['sudo', 'cat', $fileName]);
        $process->setWorkingDirectory($site->getDocumentRoot());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public function updateUserIniFile(Site $site, string $content): void
    {
        $process = new Process([
            "sudo",
            "bash",
            "-c",
            "echo " . escapeshellarg($content) . " > " . escapeshellarg('.user.ini'),
        ]);
        $process->setWorkingDirectory($site->getDocumentRoot());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->setPermission($site);
    }

    public function getFpmPool(Site $site): string
    {
        $fileName = $site->getDomainConf();

        $workingDirectory = sprintf('/etc/php/%s/fpm/pool.d/', $site->getPhpVersion());

        if (!$this->filesystem->exists($workingDirectory . $fileName)) {
            return '';
        }

        $process = new Process(['sudo', 'cat', $fileName]);
        $process->setWorkingDirectory($workingDirectory);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public function updateFpmPoolFile(Site $site, string $content): void
    {
        $fileName = $site->getDomainConf();

        $workingDirectory = sprintf('/etc/php/%s/fpm/pool.d/', $site->getPhpVersion());

        $process = new Process([
            "sudo",
            "bash",
            "-c",
            "echo " . escapeshellarg($content) . " > " . escapeshellarg($fileName),
        ]);
        $process->setWorkingDirectory($workingDirectory);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->restartFpm($site->getPhpVersion());
        $this->restartApache();
    }

    public function getAccessLog(Site $site, int $numberLines = 20): string
    {
        $path = '/var/log/apache2/' . $site->getAccessLog();
        $lines = '-' . $numberLines;

        if (!$this->filesystem->exists($path)) {
            return '';
        }

        $process = new Process(["sudo", "tail", $lines, $path]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public function getErrorLog(Site $site, int $numberLines = 20): string
    {
        $path = '/var/log/apache2/' . $site->getErrorLog();
        $lines = '-' . $numberLines;

        if (!$this->filesystem->exists($path)) {
            return '';
        }

        $process = new Process(["sudo", "tail", $lines, $path]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public function setPermission(Site $site): void
    {
        $path = $site->getSiteDirectory();

        $process = new Process(['sudo', 'chown', 'www-data:www-data', $path, '-R']);
        $process->setWorkingDirectory(dirname($path, 1));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}