<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Site;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

final class ApacheVirtualHostService
{
    protected Filesystem $filesystem;

    protected Environment $twig;

    protected ParameterBagInterface $parameterBag;

    protected string $apacheVirtualHostPath;

    private SiteService $siteService;

    private BashScriptService $bashScriptService;

    public function __construct(
        Environment $twig,
        ParameterBagInterface $parameterBag,
        SiteService $siteService,
        BashScriptService $bashScriptService
    ) {
        $this->filesystem = new Filesystem();
        $this->twig = $twig;
        $this->parameterBag = $parameterBag;
        $this->apacheVirtualHostPath = $this->parameterBag->get('apacheVirtualHostPath');
        $this->siteService = $siteService;
        $this->bashScriptService = $bashScriptService;
    }

    public function get(Site $site): string
    {
        if (!$this->filesystem->exists($this->apacheVirtualHostPath . $site->getDomainConf())) {
            return '';
        }

        $command = ['sudo', 'cat', $site->getDomainConf()];

        return $this->bashScriptService->runCommandWithReturn($command, $this->apacheVirtualHostPath);
    }

    public function create(Site $site): void
    {
        $contentVirtualHost = $this->generateContentVirtualHost($site);
        $contentVirtualHostWithSsl = $this->generateContentVirtualHostWithSsl($site);

        $content = $contentVirtualHost . $contentVirtualHostWithSsl;

        $commandContent = sprintf('echo %s > %s', escapeshellarg($content), escapeshellarg($site->getDomainConf()));

        $command = ["sudo", "bash", "-c", $commandContent];

        $this->bashScriptService->runCommandWithoutReturn($command, $this->apacheVirtualHostPath);
    }

    public function update(int $id, string $content): void
    {
        $site = $this->siteService->getOrException($id);

        if (empty($content)) {
            throw new \InvalidArgumentException('O conteúdo do VirtualHost não pode ser vazio!');
        }

        $commandContent = sprintf('echo %s > %s', escapeshellarg($content), escapeshellarg($site->getDomainConf()));

        $command = ["sudo", "bash", "-c", $commandContent];

        $this->bashScriptService->runCommandWithoutReturn($command, $this->apacheVirtualHostPath);
    }

    public function delete(Site $site): void
    {
        if (!$this->filesystem->exists($this->apacheVirtualHostPath . $site->getDomainConf())) {
            return;
        }

        $command = ['sudo', 'rm', '-f', $site->getDomainConf()];

        $this->bashScriptService->runCommandWithoutReturn($command, $this->apacheVirtualHostPath);
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
        $fileName = '/var/log/apache2/' . $site->getAccessLog();
        $lines = '-' . $numberLines;

        if (!$this->filesystem->exists($fileName)) {
            return '';
        }

        $command = ['sudo', 'tail', '-f', $lines, $fileName];

        return $this->bashScriptService->runCommandWithReturn($command);
    }

    public function getErrorLog(Site $site, int $numberLines = 20): string
    {
        $fileName = '/var/log/apache2/' . $site->getErrorLog();
        $lines = '-' . $numberLines;

        if (!$this->filesystem->exists($fileName)) {
            return '';
        }

        $command = ['sudo', 'tail', '-f', $lines, $fileName];

        return $this->bashScriptService->runCommandWithReturn($command);
    }

    public function getFpmPool(Site $site): string
    {
        $fileName = $site->getDomainConf();

        $workingDirectory = sprintf('/etc/php/%s/fpm/pool.d/', $site->getPhpVersion());

        if (!$this->filesystem->exists($workingDirectory . $fileName)) {
            return '';
        }

        $command = ['sudo', 'cat', $fileName];

        return $this->bashScriptService->runCommandWithReturn($command, $workingDirectory);
    }

    public function getUserIni(Site $site): string
    {
        $fileName = '.user.ini';

        if (!$this->filesystem->exists($site->getDocumentRoot() . DIRECTORY_SEPARATOR . $fileName)) {
            return '';
        }

        $command = ['sudo', 'cat', $fileName];

        return $this->bashScriptService->runCommandWithReturn($command, $site->getDocumentRoot());
    }
}