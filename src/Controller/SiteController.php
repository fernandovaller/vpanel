<?php

namespace App\Controller;

use App\Service\ApacheService;
use App\Service\SiteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    private SiteService $siteService;

    public function __construct(SiteService $siteService)
    {
        $this->siteService = $siteService;
    }

    /**
     * @Route("/", name="app_site_index", methods={"GET"})
     */
    public function index(): Response
    {
        $sites = $this->siteService->getAll();

        return $this->render('site/index.html.twig', [
            'sites' => $sites,
        ]);
    }

    /**
     * @Route("/site/create", name="app_site_create", methods={"POST"})
     */
    public function create(Request $request): Response
    {
        try {
            $requestData = $request->request->all();

            $this->siteService->create($requestData);

            $this->addFlash(
                'success',
                'Configuração foi criada com sucesso! Rode o comando para gerar os arquivos!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/site/{id}/edit", name="app_site_edit", methods={"GET"})
     */
    public function edit(int $id, ApacheService $apacheService): Response
    {
        try {
            $site = $this->siteService->get($id);
            $virtualHostConf = $apacheService->getVirtualHostConf($site);
            $accessLog = $apacheService->getAccessLog($site);
            $errorLog = $apacheService->getErrorLog($site);

            return $this->render('site/edit.html.twig', [
                'site' => $site,
                'virtualHostConf' => $virtualHostConf,
                'accessLog' => $accessLog,
                'errorLog' => $errorLog,
            ]);
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('app_site_index');
        }
    }

    /**
     * @Route("/site/{id}/update", name="app_site_update", methods={"POST"})
     */
    public function update(Request $request, int $id): Response
    {
        try {
            $requestData = $request->request->all();
            $site = $this->siteService->get($id);
            $this->siteService->update($requestData, $site);

            $this->addFlash(
                'success',
                'Configuração alterada com sucesso! Rode o comando para atualizar os arquivos!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }
}
