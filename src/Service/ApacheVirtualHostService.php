<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ConfigFileDto;
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

    public function get(Site $site): ConfigFileDto
    {
        $fileName = $this->apacheVirtualHostPath . $site->getDomainConf();

        if ($this->filesystem->exists($fileName)) {
            $command = ['sudo', 'cat', $site->getDomainConf()];
            $return = $this->bashScriptService->runCommandWithReturn($command, $this->apacheVirtualHostPath);
        }

        return ConfigFileDto::create()
            ->setName($fileName)
            ->setContent($return ?? '');
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

    public function getAccessLog(Site $site, int $numberLines = 20): ConfigFileDto
    {
        $fileName = '/var/log/apache2/' . $site->getAccessLog();
        $lines = '-' . $numberLines;

        if ($this->filesystem->exists($fileName)) {
            $command = ['sudo', 'tail', $lines, $fileName];
            $return = $this->bashScriptService->runCommandWithReturn($command);
        }

        return ConfigFileDto::create()
            ->setName($fileName)
            ->setContent($return ?? '');
    }

    public function getErrorLog(Site $site, int $numberLines = 20): ConfigFileDto
    {
        $fileName = '/var/log/apache2/' . $site->getErrorLog();
        $lines = '-' . $numberLines;

        if ($this->filesystem->exists($fileName)) {
            $command = ['sudo', 'tail', $lines, $fileName];
            $return = $this->bashScriptService->runCommandWithReturn($command);
        }

        return ConfigFileDto::create()
            ->setName($fileName)
            ->setContent($return ?? '');
    }

    public function getFpmPool(Site $site): ConfigFileDto
    {
        $workingDirectory = sprintf('/etc/php/%s/fpm/pool.d/', $site->getPhpVersion());
        $fileName = $workingDirectory . $site->getDomainConf();

        if ($this->filesystem->exists($fileName)) {
            $command = ['sudo', 'cat', $fileName];
            $return = $this->bashScriptService->runCommandWithReturn($command, $workingDirectory);
        }

        return ConfigFileDto::create()
            ->setName($fileName)
            ->setContent($return ?? '');
    }

    public function getUserIni(Site $site): ConfigFileDto
    {
        $fileName = $site->getDocumentRoot() . DIRECTORY_SEPARATOR . '.user.ini';

        if ($this->filesystem->exists($fileName)) {
            $command = ['sudo', 'cat', $fileName];
            $return = $this->bashScriptService->runCommandWithReturn($command);
        }

        return ConfigFileDto::create()
            ->setName($fileName)
            ->setContent($return ?? '');
    }
}