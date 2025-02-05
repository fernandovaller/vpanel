<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Site;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Twig\Environment;

final class ApacheVirtualHostService
{
    protected Filesystem $filesystem;

    protected Environment $twig;

    protected ParameterBagInterface $parameterBag;

    protected string $apacheVirtualHostPath;

    private SiteService $siteService;

    public function __construct(
        Environment $twig,
        ParameterBagInterface $parameterBag,
        SiteService $siteService
    ) {
        $this->filesystem = new Filesystem();
        $this->twig = $twig;
        $this->parameterBag = $parameterBag;
        $this->apacheVirtualHostPath = $this->parameterBag->get('apacheVirtualHostPath');
        $this->siteService = $siteService;
    }

    public function get(Site $site): string
    {
        if (!$this->filesystem->exists($this->apacheVirtualHostPath . $site->getDomainConf())) {
            return '';
        }

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

    public function update(int $id, string $content): void
    {
        $site = $this->siteService->get($id);

        if ($site === null) {
            throw new \InvalidArgumentException('Site não existe!');
        }

        if (empty($content)) {
            throw new \InvalidArgumentException('O conteúdo do VirtualHost não pode ser vazio!');
        }

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
        if (!$this->filesystem->exists($this->apacheVirtualHostPath . $site->getDomainConf())) {
            return;
        }

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
            'accessLog' => $site->getAccessLog(),
            'errorLog' => $site->getErrorLog(),
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
            'accessLog' => $site->getAccessLog(),
            'errorLog' => $site->getErrorLog(),
            'certPath' => $certPath,
        ]);
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
}