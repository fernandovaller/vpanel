<?php

namespace App\Controller;

use App\Service\ApacheService;
use App\Service\PhpVersionService;
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
    public function index(Request $request, ApacheService $apacheService): Response
    {
        $page = $request->query->getInt('page', 1);

        $pagination = $this->siteService->getAll($page);

        return $this->render('site/index.html.twig', [
            'pagination' => $pagination,
            'apacheStatus' => $apacheService->isRunning(),
        ]);
    }

    /**
     * @Route("/site/create", name="app_site_create", methods={"GET"})
     */
    public function create(PhpVersionService $phpVersionService): Response
    {
        try {
            return $this->render('site/create.html.twig', [
                'phpVersions' => $phpVersionService->getList(),
            ]);
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/site/store", name="app_site_store", methods={"POST"})
     */
    public function store(Request $request): Response
    {
        try {
            $requestData = $request->request->all();

            $this->siteService->create($requestData);

            $this->addFlash(
                'success',
                'Site criado! Execute o comando para gerar os arquivos de configuração!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/site/{id}/edit", name="app_site_edit", methods={"GET"})
     */
    public function edit(
        int $id,
        PhpVersionService $phpVersionService,
        ApacheService $apacheService
    ): Response {
        try {
            $site = $this->siteService->get($id);

            return $this->render('site/edit.html.twig', [
                'site' => $site,
                'phpVersions' => $phpVersionService->getList(),
                'apacheVirtualHostDto' => $apacheService->getInfo($site),
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
                'Site atualizado! Execute o comando para gerar os arquivos de configuração!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }
}
