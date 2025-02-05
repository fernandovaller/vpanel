<?php

namespace App\Controller;

use App\Service\ApacheService;
use App\Service\ApacheVirtualHostService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApacheVirtualHostController extends AbstractController
{
    private ApacheService $apacheService;

    private ApacheVirtualHostService $apacheVirtualHostService;

    public function __construct(
        ApacheService $apacheService,
        ApacheVirtualHostService $apacheVirtualHostService
    ) {
        $this->apacheService = $apacheService;
        $this->apacheVirtualHostService = $apacheVirtualHostService;
    }

    /**
     * @Route("/apache/virtualhost/host", name="app_apache_virtualhost_index")
     */
    public function index(): Response
    {
        return $this->render('apache_virtual_host/index.html.twig', [
            'controller_name' => 'ApacheVirtualHostController',
        ]);
    }

    /**
     * @Route("/apache/virtualhost/{id}/create", name="app_apache_virtualhost_create", methods={"GET"})
     */
    public function create(int $id): Response
    {
        try {
            $this->apacheService->create($id);

            $this->addFlash('success', 'VirtualHost criado! O site deve está disponível para acesso!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/apache/virtualhost/{id}/delete", name="app_apache_virtualhost_delete", methods={"GET"})
     */
    public function delete(int $id): Response
    {
        try {
            $this->apacheService->delete($id);

            $this->addFlash(
                'success',
                'VirtualHost removido! Os arquivos de configuração também foram removidos!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/apache/virtualhost/{id}/update", name="app_apache_virtualhost_update", methods={"POST"})
     */
    public function update(Request $request, int $id): Response
    {
        try {
            $content = $request->request->get('virtualHostConf');

            $this->apacheVirtualHostService->update($id, $content);

            $this->addFlash('success', 'VirtualHost atualizado!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }
}
