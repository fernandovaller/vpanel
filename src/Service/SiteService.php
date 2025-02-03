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
            ->setPhpVersion($requestData['phpVersion']);

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