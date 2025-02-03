<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Site;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class ApacheService
{
    protected ParameterBagInterface $parameterBag;

    protected ApacheVirtualHostFileService $apacheVirtualHostFileService;

    protected MkcertService $mkcertService;

    public function __construct(
        ParameterBagInterface $parameterBag,
        ApacheVirtualHostFileService $apacheVirtualHostFileService,
        MkcertService $mkcertService

    ) {
        $this->parameterBag = $parameterBag;
        $this->apacheVirtualHostFileService = $apacheVirtualHostFileService;
        $this->mkcertService = $mkcertService;
    }

    public function create(Site $site): void
    {
        $this->apacheVirtualHostFileService->create($site);
        $this->mkcertService->generate($site->getDomain());
        $this->enableSite($site->getDomainConf());
        $this->appendSiteInHosts($site->getDomain());
        $this->restart();

        $this->createFolder($site);
        $this->createFile($site);
    }

    public function delete(Site $site): void
    {
        $this->apacheVirtualHostFileService->delete($site);
        $this->mkcertService->delete($site->getDomain());
        $this->disableSite($site->getDomainConf());
        $this->restart();
    }

    private function enableSite(string $fileName): void
    {
        $filePath = $this->parameterBag->get('apacheVirtualHostPath');

        $process = new Process(['sudo', 'a2ensite', $fileName]);
        $process->setWorkingDirectory($filePath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function disableSite(string $fileName): void
    {
        $filePath = $this->parameterBag->get('apacheVirtualHostPath');

        $process = new Process(['sudo', 'a2dissite', $fileName]);
        $process->setWorkingDirectory($filePath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function restart(): void
    {
        $process = new Process(['sudo', 'service', 'apache2', 'reload']);
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
        $content = sprintf('127.0.0.1       %s', $domain);

        $process = new Process([
            "sudo",
            "bash",
            "-c",
            "echo " . escapeshellarg($content) . " >> /etc/hosts" . PHP_EOL,
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}