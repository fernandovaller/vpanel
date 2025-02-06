<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ConfigFileDto;
use App\Dto\PhpVersionStatusDto;
use App\Entity\PhpVersion;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

final class PhpVersionService
{
    private EntityManagerInterface $entityManager;

    private PaginatorInterface $paginator;

    private BashScriptService $bashScriptService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        BashScriptService $bashScriptService
    ) {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
        $this->bashScriptService = $bashScriptService;
    }

    public function get(int $id): ?PhpVersion
    {
        return $this->entityManager->getRepository(PhpVersion::class)->find($id);
    }

    public function getByVersion(string $version): ?PhpVersion
    {
        return $this->entityManager->getRepository(PhpVersion::class)->findOneBy([
            'version' => $version,
        ]);
    }

    public function getOrException(int $id): PhpVersion
    {
        $phpVersion = $this->get($id);

        if ($phpVersion === null) {
            throw new \InvalidArgumentException('PhpVersion não existe!');
        }

        return $phpVersion;
    }

    public function getList(): array
    {
        return $this->entityManager->getRepository(PhpVersion::class)->findAll();
    }

    public function getAll(int $page = 1): PaginationInterface
    {
        $query = $this->entityManager->getRepository(PhpVersion::class)
            ->createQueryBuilder('p')
            ->orderBy('p.version', 'ASC')
            ->getQuery();

        return $this->paginator->paginate($query, $page, 10);
    }

    public function update(): void
    {
        $list = $this->getPhpVersionsInstalled();

        if (empty($list)) {
            return;
        }

        foreach ($list as $version) {
            $versionNumber = str_replace('/usr/bin/php', '', $version);

            $hasVersion = $this->getByVersion($versionNumber);

            if ($hasVersion !== null) {
                continue;
            }

            $phpVersion = (new PhpVersion())
                ->setVersion($versionNumber)
                ->setPath($version);

            $this->entityManager->persist($phpVersion);
        }

        $this->entityManager->flush();
    }

    public function getStatus(): array
    {
        $list = $this->getList();

        $status = [];
        foreach ($list as $version) {
            $status[$version->getVersion()] = $this->isRunning($version->getVersion());
        }

        return $status;
    }

    public function isRunning(string $version): bool
    {
        $command = ['systemctl', 'is-active', sprintf('php%s-fpm', $version)];

        $output = $this->bashScriptService->runCommandWithReturn($command, null, false);

        $status = preg_replace("/[^a-zA-Z]+/", '', $output);

        return $status === 'active';
    }

    public function changeStatus(int $id, string $acton): void
    {
        $phpVersion = $this->getOrException($id);

        $fileName = sprintf(' php%s-fpm', $phpVersion->getVersion());

        $command = ['sudo', 'service', $fileName, $acton];

        $this->bashScriptService->runCommandWithoutReturn($command);
    }

    private function deleteAll(): void
    {
        $this->entityManager
            ->createQueryBuilder()
            ->delete(PhpVersion::class, 'e')
            ->getQuery()
            ->execute();
    }

    /**
     * @return array<string>
     */
    private function getPhpVersionsInstalled(): array
    {
        $command = ['update-alternatives', '--list', 'php'];

        $return = $this->bashScriptService->runCommandWithReturn($command);

        return array_filter(explode(PHP_EOL, $return));
    }

    public function getIni(PhpVersion $phpVersion): ConfigFileDto
    {
        $fileName = sprintf('/etc/php/%s/fpm/php.ini', $phpVersion->getVersion());

        $command = ['cat', $fileName];

        $return = $this->bashScriptService->runCommandWithReturn($command);

        return ConfigFileDto::create()
            ->setName($fileName)
            ->setContent($return);
    }

    public function updateIni(int $id, string $content): void
    {
        $phpVersion = $this->getOrException($id);

        $fileName = sprintf('/etc/php/%s/fpm/php.ini', $phpVersion->getVersion());

        $commandContent = sprintf('echo %s > %s', escapeshellarg($content), escapeshellarg($fileName));

        $command = ['sudo', 'bash', '-c', $commandContent];

        $this->bashScriptService->runCommandWithoutReturn($command);
    }
}