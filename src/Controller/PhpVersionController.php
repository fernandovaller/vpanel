<?php

namespace App\Controller;

use App\Service\ApacheService;
use App\Service\PhpVersionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PhpVersionController extends AbstractController
{
    private PhpVersionService $phpVersionService;

    public function __construct(PhpVersionService $phpVersionService)
    {
        $this->phpVersionService = $phpVersionService;
    }

    /**
     * @Route("/phpversion", name="app_phpversion_index", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);

        $pagination = $this->phpVersionService->getAll($page);

        return $this->render('php_version/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/phpversion/update", name="app_phpversion_update", methods={"GET"})
     */
    public function update(): Response
    {
        try {
            $this->phpVersionService->update();

            $this->addFlash(
                'success',
                'Lista de versões do PHP foi atualizada com sucesso!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_phpversion_index');
    }

    /**
     * @Route("/phpversion/{id}/edit", name="app_phpversion_edit", methods={"GET"})
     */
    public function edit(int $id, PhpVersionService $phpVersionService): Response
    {
        try {
            $phpVersion = $phpVersionService->get($id);

            return $this->render('php_version/edit.html.twig', [
                'entity' => $phpVersion,
                'configFileDto' => $phpVersionService->getIni($phpVersion),
            ]);
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('app_phpversion_index');
        }
    }
}
