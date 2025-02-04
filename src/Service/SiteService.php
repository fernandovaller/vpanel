<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

final class SiteService
{
    private EntityManagerInterface $entityManager;

    private PaginatorInterface $paginator;

    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $paginator)
    {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
    }

    public function get(int $id): ?Site
    {
        return $this->entityManager->getRepository(Site::class)->find($id);
    }

    public function getAll(int $page = 1): PaginationInterface
    {
        $query = $this->entityManager->getRepository(Site::class)
            ->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->getQuery();
        
        return $this->paginator->paginate($query, $page, 10);
    }

    public function create(array $requestData): ?Site
    {
        $this->validate($requestData);

        $site = (new Site())
            ->setDomain(trim($requestData['domain']))
            ->setTitle($requestData['title'])
            ->setDocumentRoot(trim($requestData['documentRoot']))
            ->setPhpVersion($requestData['phpVersion']);

        $this->entityManager->persist($site);
        $this->entityManager->flush();

        return $site;
    }

    public function update(array $requestData, Site $site): ?Site
    {
        $this->validate($requestData);

        $site
            ->setDomain(trim($requestData['domain']))
            ->setTitle($requestData['title'])
            ->setDocumentRoot(trim($requestData['documentRoot']))
            ->setPhpVersion($requestData['phpVersion'])
            ->setSiteDirectory(trim($requestData['siteDirectory']))
            ->setDefaultDocument(trim($requestData['defaultDocument']));

        $this->entityManager->flush();

        return $site;
    }

    public function delete(Site $site): void
    {
        $this->entityManager->remove($site);
        $this->entityManager->flush();
    }

    private function validate(array $requestData): void
    {
        if (empty($requestData['domain'])) {
            throw new \InvalidArgumentException('Domain is required');
        }

        if (empty($requestData['title'])) {
            throw new \InvalidArgumentException('Title is required');
        }

        if (empty($requestData['documentRoot'])) {
            throw new \InvalidArgumentException('DocumentRoot is required');
        }

        if (empty($requestData['phpVersion'])) {
            throw new \InvalidArgumentException('PhpVersion is required');
        }
    }
}