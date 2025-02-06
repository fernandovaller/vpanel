<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DatabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DatabaseController extends AbstractController
{
    private DatabaseService $databaseService;

    private TranslatorInterface $translator;

    public function __construct(
        DatabaseService $databaseService,
        TranslatorInterface $translator
    ) {
        $this->databaseService = $databaseService;
        $this->translator = $translator;
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
     * @Route("/database/create", name="app_database_create", methods={"GET"})
     */
    public function create(): Response
    {
        try {
            return $this->render('database/create.html.twig');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_database_index');
    }

    /**
     * @Route("/database/store", name="app_database_store", methods={"POST"})
     */
    public function store(Request $request): Response
    {
        try {
            $requestData = $request->request->all();

            $this->databaseService->create($requestData);

            $this->addFlash('success', $this->translator->trans('database.form.create'));
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

            return $this->render('database/edit.html.twig', [
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

            $this->addFlash('success', $this->translator->trans('database.form.edit'));
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
            $this->databaseService->delete($id);

            $this->addFlash('success', $this->translator->trans('database.form.delete'));
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

            $this->addFlash('success', $this->translator->trans('database.form.create'));
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_database_index');
    }
}
