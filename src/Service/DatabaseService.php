<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ConfigFileDto;
use App\Entity\Database;
use App\Enum\ServiceActionEnum;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Filesystem\Filesystem;

final class DatabaseService
{
    private EntityManagerInterface $entityManager;

    private PaginatorInterface $paginator;

    private DatabaseMySqlService $databaseMySqlService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        DatabaseMySqlService $databaseMySqlService

    ) {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
        $this->databaseMySqlService = $databaseMySqlService;
    }

    public function get(int $id): ?Database
    {
        return $this->entityManager->getRepository(Database::class)->find($id);
    }

    public function getAll(int $page = 1): PaginationInterface
    {
        $query = $this->entityManager->getRepository(Database::class)
            ->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->getQuery();

        return $this->paginator->paginate($query, $page, 10);
    }

    public function create(array $requestData): ?Database
    {
        $this->validate($requestData);

        $database = (new Database())
            ->setName($requestData['name'])
            ->setUsername($requestData['username'])
            ->setPassword($requestData['password'])
            ->setPermission($requestData['permission'])
            ->setNote($requestData['note'] ?? null);

        $this->entityManager->persist($database);
        $this->entityManager->flush();

        return $database;
    }

    public function update(array $requestData, int $id): void
    {
        $database = $this->get($id);

        if ($database === null) {
            throw new \RuntimeException('Database not found');
        }

        $this->validate($requestData);

        $database
            ->setName($requestData['name'])
            ->setUsername($requestData['username'])
            ->setPassword($requestData['password'])
            ->setPermission($requestData['permission'])
            ->setNote($requestData['note'] ?? null);

        $this->entityManager->flush();
    }

    public function delete(int $id): void
    {
        $database = $this->get($id);

        if ($database === null) {
            throw new \RuntimeException('Database not found');
        }

        $this->removeDatabase($database);

        $this->entityManager->remove($database);
        $this->entityManager->flush();
    }

    private function validate(array $requestData): void
    {
        if (empty($requestData['name'])) {
            throw new \InvalidArgumentException('name is required');
        }

        if (empty($requestData['username'])) {
            throw new \InvalidArgumentException('username is required');
        }

        if (empty($requestData['password'])) {
            throw new \InvalidArgumentException('password is required');
        }
    }

    public function generateDatabase(int $id): void
    {
        $database = $this->get($id);

        if ($database === null) {
            throw new \RuntimeException('Database not found');
        }

        if ($this->isRunning() === false) {
            throw new \RuntimeException('Database not active');
        }

        $this->databaseMySqlService->createDatabase($database);
        $this->databaseMySqlService->createUser($database);
        $this->databaseMySqlService->grantAllPrivileges($database);
        $this->databaseMySqlService->flushPrivileges();
    }

    private function removeDatabase(Database $database): void
    {
        if ($this->isRunning() === false) {
            throw new \RuntimeException('Database not active');
        }

        if ($this->databaseMySqlService->userExists($database) === false) {
            return;
        }

        $this->databaseMySqlService->dropDatabase($database);
        $this->databaseMySqlService->dropUser($database);
        $this->databaseMySqlService->flushPrivileges();
    }

    public function isRunning(): bool
    {
        return $this->databaseMySqlService->isRunning();
    }

    public function getMySQLConfig(): ConfigFileDto
    {
        $fileName = '/etc/mysql/my.cnf';

        return $this->databaseMySqlService->getContentFile($fileName);
    }

    public function changeStatus(string $action): void
    {
        if (ServiceActionEnum::isValidValue($action) === false) {
            throw new \InvalidArgumentException('Invalid action');
        }

        $this->databaseMySqlService->changeStatus($action);
    }
}
