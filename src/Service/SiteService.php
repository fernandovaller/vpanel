<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;

final class SiteService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function get(int $id): ?Site
    {
        return $this->entityManager->getRepository(Site::class)->find($id);
    }

    public function getAll(): array
    {
        return $this->entityManager->getRepository(Site::class)->findAll();
    }

    public function create(array $requestData): ?Site
    {
        $site = (new Site())
            ->setDomain($requestData['domain'])
            ->setTitle($requestData['title'])
            ->setDocumentRoot($requestData['documentRoot'])
            ->setPhpVersion($requestData['phpVersion']);

        $this->entityManager->persist($site);
        $this->entityManager->flush();

        return $site;
    }

    public function update(array $requestData, Site $site): ?Site
    {
        $site
            ->setDomain($requestData['domain'])
            ->setTitle($requestData['title'])
            ->setDocumentRoot($requestData['documentRoot'])
            ->setPhpVersion($requestData['phpVersion']);

        $this->entityManager->flush();

        return $site;
    }
}