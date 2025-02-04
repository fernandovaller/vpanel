<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Site;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Twig\Environment;

final class ApacheVirtualHostFileService
{
    protected Filesystem $filesystem;

    protected Environment $twig;

    protected ParameterBagInterface $parameterBag;

    protected string $apacheVirtualHostPath;

    public function __construct(Environment $twig, ParameterBagInterface $parameterBag)
    {
        $this->filesystem = new Filesystem();
        $this->twig = $twig;
        $this->parameterBag = $parameterBag;
        $this->apacheVirtualHostPath = $this->parameterBag->get('apacheVirtualHostPath');
    }

    public function get($site): string
    {
        $process = new Process(['sudo', 'cat', $site->getDomainConf()]);
        $process->setWorkingDirectory($this->apacheVirtualHostPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public function create(Site $site): void
    {
        $content = $this->generateContentVirtualHost($site);
        $content .= $this->generateContentVirtualHostWithSsl($site);

        $process = new Process([
            "sudo",
            "bash",
            "-c",
            "echo " . escapeshellarg($content) . " > " . escapeshellarg($site->getDomainConf()),
        ]);
        $process->setWorkingDirectory($this->apacheVirtualHostPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function update(Site $site, string $content): void
    {
        $this->delete($site);

        $process = new Process([
            "sudo",
            "bash",
            "-c",
            "echo " . escapeshellarg($content) . " > " . escapeshellarg($site->getDomainConf()),
        ]);
        $process->setWorkingDirectory($this->apacheVirtualHostPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function delete(Site $site): void
    {
        $process = new Process(['sudo', 'rm', '-f', $site->getDomainConf()]);
        $process->setWorkingDirectory($this->apacheVirtualHostPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function generateContentVirtualHost(Site $site): string
    {
        return $this->twig->render('apache/virtualHost.html.twig', [
            'domain' => $site->getDomain(),
            'documentRoot' => $site->getDocumentRoot(),
            'phpVersion' => $site->getPhpVersion(),
            'defaultDocument' => $site->getDefaultDocument(),
        ]);
    }

    private function generateContentVirtualHostWithSsl(Site $site): string
    {
        $certPath = $this->parameterBag->get('mkcertPath');

        return $this->twig->render('apache/virtualHostSsl.html.twig', [
            'domain' => $site->getDomain(),
            'documentRoot' => $site->getDocumentRoot(),
            'phpVersion' => $site->getPhpVersion(),
            'defaultDocument' => $site->getDefaultDocument(),
            'certPath' => $certPath,
        ]);
    }
}