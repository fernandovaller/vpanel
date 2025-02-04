<?php

namespace App\Controller;

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
     * @Route("/php/version", name="app_php_version_index", methods={"GET"})
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
     * @Route("/php/version-update", name="app_php_version_update", methods={"GET"})
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

        return $this->redirectToRoute('app_php_version_index');
    }
}
