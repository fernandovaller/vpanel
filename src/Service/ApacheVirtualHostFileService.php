<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Site;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class ApacheVirtualHostFileService
{
    protected Filesystem $filesystem;

    protected Environment $twig;

    protected ParameterBagInterface $parameterBag;

    public function __construct(Environment $twig, ParameterBagInterface $parameterBag)
    {
        $this->filesystem = new Filesystem();
        $this->twig = $twig;
        $this->parameterBag = $parameterBag;
    }

    public function create(Site $site): void
    {
        $apacheVirtualHostPath = $this->parameterBag->get('apacheVirtualHostPath');

        $content = $this->generateContentVirtualHost($site);
        $content .= $this->generateContentVirtualHostWithSsl($site);

        $process = new Process([
            "sudo",
            "bash",
            "-c",
            "echo " . escapeshellarg($content) . " > " . escapeshellarg($site->getDomainConf()),
        ]);
        $process->setWorkingDirectory($apacheVirtualHostPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
    
    public function delete(Site $site): void
    {
        $apacheVirtualHostPath = $this->parameterBag->get('apacheVirtualHostPath');

        $process = new Process(['sudo', 'rm', '-f', $site->getDomainConf()]);
        $process->setWorkingDirectory($apacheVirtualHostPath);
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
        ]);
    }

    private function generateContentVirtualHostWithSsl(Site $site): string
    {
        $certPath = $this->parameterBag->get('mkcertPath');

        return $this->twig->render('apache/virtualHostSsl.html.twig', [
            'domain' => $site->getDomain(),
            'documentRoot' => $site->getDocumentRoot(),
            'phpVersion' => $site->getPhpVersion(),
            'certPath' => $certPath,
        ]);
    }
}