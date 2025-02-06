<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DatabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DatabaseController extends AbstractController
{
    private DatabaseService $databaseService;

    public function __construct(DatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    /**
     * @Route("/database", name="app_database_index")
     */
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);

        $pagination = $this->databaseService->getAll($page);

        return $this->render('database/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/database/create", name="app_database_create", methods={"POST"})
     */
    public function create(Request $request): Response
    {
        try {
            $requestData = $request->request->all();

            $this->databaseService->create($requestData);

            $this->addFlash(
                'success',
                'Database criada!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_database_index');
    }

    /**
     * @Route("/database/{id}/edit", name="app_database_edit", methods={"GET"})
     */
    public function edit(int $id): Response
    {
        try {
            $database = $this->databaseService->get($id);

            return $this->render('database/editForm.html.twig', [
                'database' => $database,
            ]);
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('app_database_index');
        }
    }

    /**
     * @Route("/database/{id}/update", name="app_database_update", methods={"POST"})
     */
    public function update(Request $request, int $id): Response
    {
        try {
            $requestData = $request->request->all();

            $this->databaseService->update($requestData, $id);

            $this->addFlash(
                'success',
                'Database atualizada!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_database_index');
    }

    /**
     * @Route("/database/{id}/delete", name="app_database_delete", methods={"GET"})
     */
    public function delete(int $id): Response
    {
        try {
            $this->databaseService->removeDatabase($id);
            $this->databaseService->delete($id);

            $this->addFlash(
                'success',
                'Database removida!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_database_index');
    }

    /**
     * @Route("/database/{id}/generate", name="app_database_generate", methods={"GET"})
     */
    public function generate(int $id): Response
    {
        try {
            $this->databaseService->generateDatabase($id);

            $this->addFlash(
                'success',
                'Database gerada!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_database_index');
    }
}
