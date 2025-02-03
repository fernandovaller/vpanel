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
        $this->restart();

        $this->createFolder($site);
        $this->createFile($site);
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

    private function restart(): void
    {
        $process = new Process(['sudo', 'service', 'apache2', 'restart']);
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
}