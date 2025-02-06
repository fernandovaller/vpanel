<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ApacheVirtualHostDto;
use App\Entity\Site;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

final class ApacheService
{
    protected ParameterBagInterface $parameterBag;

    protected ApacheVirtualHostService $apacheVirtualHostService;

    protected MkcertService $mkcertService;

    private string $apacheVirtualHostPath;

    private Filesystem $filesystem;

    private SiteService $siteService;

    private BashScriptService $bashScriptService;

    public function __construct(
        ParameterBagInterface $parameterBag,
        ApacheVirtualHostService $apacheVirtualHostService,
        MkcertService $mkcertService,
        SiteService $siteService,
        BashScriptService $bashScriptService
    ) {
        $this->parameterBag = $parameterBag;
        $this->apacheVirtualHostService = $apacheVirtualHostService;
        $this->mkcertService = $mkcertService;
        $this->apacheVirtualHostPath = $this->parameterBag->get('apacheVirtualHostPath');
        $this->filesystem = new Filesystem();
        $this->siteService = $siteService;
        $this->bashScriptService = $bashScriptService;
    }

    public function create(int $id): void
    {
        $site = $this->siteService->getOrException($id);

        $this->apacheVirtualHostService->create($site);
        $this->mkcertService->generate($site->getDomain());
        $this->enableSite($site->getDomainConf());
        $this->appendSiteInHosts($site->getDomain());
        $this->restart();

        $this->createFolder($site);
        $this->createFile($site);
        $this->setPermission($site);
    }

    public function delete(int $id): void
    {
        $site = $this->siteService->getOrException($id);

        $this->apacheVirtualHostService->delete($site);
        $this->mkcertService->delete($site->getDomain());
        $this->disableSite($site->getDomainConf());
        $this->restart();

        $this->siteService->delete($site);
    }

    private function enableSite(string $fileName): void
    {
        $command = ['sudo', 'a2ensite', $fileName];

        $this->bashScriptService->runCommandWithoutReturn($command, $this->apacheVirtualHostPath);
    }

    private function disableSite(string $fileName): void
    {
        if (!$this->filesystem->exists($this->apacheVirtualHostPath . $fileName)) {
            return;
        }

        $command = ['sudo', 'a2dissite', $fileName];

        $this->bashScriptService->runCommandWithoutReturn($command, $this->apacheVirtualHostPath);
    }

    public function isRunning(): bool
    {
        $command = ['systemctl', 'is-active', 'apache2'];

        $output = $this->bashScriptService->runCommandWithReturn($command, null, false);

        $status = preg_replace("/[^a-zA-Z]+/", '', $output);

        return $status === 'active';
    }

    public function start(): void
    {
        $command = ['sudo', 'service', 'apache2', 'start'];

        $this->bashScriptService->runCommandWithoutReturn($command);
    }

    public function stop(): void
    {
        $command = ['sudo', 'service', 'apache2', 'stop'];

        $this->bashScriptService->runCommandWithoutReturn($command);
    }

    public function restart(): void
    {
        if ($this->isRunning() === false) {
            return;
        }

        $command = ['sudo', 'service', 'apache2', 'restart'];

        $this->bashScriptService->runCommandWithoutReturn($command);
    }

    public function reload(): void
    {
        if ($this->isRunning() === false) {
            return;
        }

        $command = ['sudo', 'service', 'apache2', 'reload'];

        $this->bashScriptService->runCommandWithoutReturn($command);
    }

    private function restartPhpFpm(string $phpVersion): void
    {
        $phpFpmVersion = sprintf('php%s-fpm', $phpVersion);

        $command = ['sudo', 'systemctl', 'restart', $phpFpmVersion];

        $this->bashScriptService->runCommandWithoutReturn($command);
    }

    private function createFolder(Site $site): void
    {
        $command = ['sudo', 'mkdir', '-p', $site->getDocumentRoot()];

        $this->bashScriptService->runCommandWithoutReturn($command);
    }

    private function createFile(Site $site): void
    {
        $fileName = 'index.php';
        $fileContent = '<?php echo phpinfo();';

        $commandContent = sprintf('echo %s > %s', escapeshellarg($fileContent), escapeshellarg($fileName));

        $command = ['sudo', 'bash', '-c', $commandContent];

        $this->bashScriptService->runCommandWithoutReturn($command, $site->getDocumentRoot());
    }

    private function appendSiteInHosts(string $domain): void
    {
        $domainRow = sprintf('127.0.0.1       %s', $domain);

        $command = sprintf('grep -qxF "%s" /etc/hosts || echo "%s" | sudo tee -a /etc/hosts', $domainRow, $domainRow);

        $this->bashScriptService->runCommandLineWithoutReturn($command);
    }

    public function updateUserIniFile(int $siteId, string $content): void
    {
        $site = $this->siteService->getOrException($siteId);

        $fileName = '.user.ini';
        $fileContent = $content;

        $commandContent = sprintf('echo %s > %s', escapeshellarg($fileContent), escapeshellarg($fileName));

        $command = ['sudo', 'bash', '-c', $commandContent];

        $this->bashScriptService->runCommandWithoutReturn($command, $site->getDocumentRoot());

        $this->setPermission($site);
    }

    public function updateFpmPoolFile(int $siteId, string $content): void
    {
        $site = $this->siteService->getOrException($siteId);

        $fileName = $site->getDomainConf();
        $fileContent = $content;

        $workingDirectory = sprintf('/etc/php/%s/fpm/pool.d/', $site->getPhpVersion());

        $commandContent = sprintf('echo %s > %s', escapeshellarg($fileContent), escapeshellarg($fileName));

        $command = ['sudo', 'bash', '-c', $commandContent];

        $this->bashScriptService->runCommandWithoutReturn($command, $workingDirectory);

        $this->restartPhpFpm($site->getPhpVersion());
        $this->restart();
    }

    public function setPermission(Site $site): void
    {
        $siteDirectory = $site->getSiteDirectory();

        $command = ['sudo', 'chown', 'www-data:www-data', $siteDirectory, '-R'];

        $this->bashScriptService->runCommandWithoutReturn($command, dirname($siteDirectory, 1));
    }

    public function getApacheConf(): string
    {
        $fileName = '/etc/apache2/apache2.conf';

        if (!$this->filesystem->exists($fileName)) {
            return '';
        }

        $command = ['sudo', 'cat', $fileName];

        return $this->bashScriptService->runCommandWithReturn($command);
    }

    public function updateApacheConf(string $content): void
    {
        if (empty($content)) {
            throw new \LogicException('Empty apache conf');
        }

        $fileName = '/etc/apache2/apache2.conf';
        $fileContent = $content;

        $commandContent = sprintf('echo %s > %s', escapeshellarg($fileContent), escapeshellarg($fileName));

        $command = ['sudo', 'bash', '-c', $commandContent];

        $this->bashScriptService->runCommandWithoutReturn($command);

        $this->restart();
    }

    public function getApacheError(int $lines = 20): string
    {
        $fileName = '/var/log/apache2/error.log';
        $numberOfLines = '-' . $lines;

        if (!$this->filesystem->exists($fileName)) {
            return '';
        }

        $command = ['sudo', 'tail', $numberOfLines, $fileName];

        return $this->bashScriptService->runCommandWithReturn($command);
    }

    public function getInfo(?Site $site): ApacheVirtualHostDto
    {
        return ApacheVirtualHostDto::create()
            ->setVirtualHost($this->apacheVirtualHostService->get($site))
            ->setUserIni($this->apacheVirtualHostService->getUserIni($site))
            ->setFpmPool($this->apacheVirtualHostService->getFpmPool($site))
            ->setAccessLog($this->apacheVirtualHostService->getAccessLog($site))
            ->setErrorLog($this->apacheVirtualHostService->getErrorLog($site));
    }
}