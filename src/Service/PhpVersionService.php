<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PhpVersion;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class PhpVersionService
{
    private EntityManagerInterface $entityManager;

    private PaginatorInterface $paginator;

    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $paginator)
    {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
    }

    public function get(int $id): ?PhpVersion
    {
        return $this->entityManager->getRepository(PhpVersion::class)->find($id);
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

        $this->deleteAll();

        foreach ($list as $version) {
            $versionNumber = str_replace('/usr/bin/php', '', $version);

            $phpVersion = (new PhpVersion())
                ->setVersion($versionNumber)
                ->setPath($version);

            $this->entityManager->persist($phpVersion);
        }

        $this->entityManager->flush();
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
        $process = new Process(['update-alternatives', '--list', 'php']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return array_filter(
            explode(PHP_EOL, $process->getOutput())
        );
    }
}