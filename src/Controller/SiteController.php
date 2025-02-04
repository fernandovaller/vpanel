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

    private PhpVersionService $phpVersionService;

    public function __construct(SiteService $siteService, PhpVersionService $phpVersionService)
    {
        $this->siteService = $siteService;
        $this->phpVersionService = $phpVersionService;
    }

    /**
     * @Route("/", name="app_site_index", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);

        $pagination = $this->siteService->getAll($page);

        return $this->render('site/index.html.twig', [
            'pagination' => $pagination,
            'phpVersions' => $this->phpVersionService->getList(),
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
    public function edit(int $id, ApacheService $apacheService): Response
    {
        try {
            $site = $this->siteService->get($id);
            $virtualHostConf = $apacheService->getVirtualHostConf($site);
            $userIni = $apacheService->getUserIni($site);
            $fpmPool = $apacheService->getFpmPool($site);
            $accessLog = $apacheService->getAccessLog($site);
            $errorLog = $apacheService->getErrorLog($site);

            return $this->render('site/edit.html.twig', [
                'site' => $site,
                'phpVersions' => $this->phpVersionService->getList(),
                'virtualHostConf' => $virtualHostConf,
                'userIni' => $userIni,
                'fpmPool' => $fpmPool,
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
                'Site atualizado! Execute o comando para gerar os arquivos de configuração!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }
}
