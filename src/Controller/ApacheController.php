<?php

namespace App\Controller;

use App\Service\ApacheService;
use App\Service\SiteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ApacheController extends AbstractController
{
    protected ApacheService $apacheService;

    protected SiteService $siteService;

    public function __construct(
        ApacheService $apacheService,
        SiteService $siteService
    ) {
        $this->apacheService = $apacheService;
        $this->siteService = $siteService;
    }

    /**
     * @Route("/apache/{id}/create", name="app_apache_create_site", methods={"GET"})
     */
    public function createSite(int $id): Response
    {
        try {
            $site = $this->siteService->get($id);

            if ($site === null) {
                throw new NotFoundHttpException('Site não existe!');
            }

            $this->apacheService->create($site);

            $this->addFlash('success', 'Site ativo! Os arquivos de configuração foram criados!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/apache/{id}/delete", name="app_apache_delete_site", methods={"GET"})
     */
    public function deleteSite(int $id): Response
    {
        try {
            $site = $this->siteService->get($id);

            if ($site === null) {
                throw new NotFoundHttpException('Site não existe!');
            }

            $this->apacheService->delete($site);
            $this->siteService->delete($site);

            $this->addFlash(
                'success',
                'Site removido! Os arquivos de configuração também foram removidos!'
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/apache/{id}/update-virtualhost", name="app_apache_update_virtualhost", methods={"POST"})
     */
    public function updateVirtualHost(Request $request, int $id): Response
    {
        try {
            $virtualHostConf = $request->request->get('virtualHostConf');

            $site = $this->siteService->get($id);

            if ($site === null) {
                throw new NotFoundHttpException('Site não existe!');
            }

            $this->apacheService->updateVirtualHostConf($site, $virtualHostConf);

            $this->addFlash('success', 'Arquivo de configuração do site foi atualizado com sucesso!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/apache/{id}/userini", name="app_apache_userini", methods={"POST"})
     */
    public function createUserIni(Request $request, int $id): Response
    {
        try {
            $userIni = $request->request->get('userIni');

            $site = $this->siteService->get($id);

            if ($site === null) {
                throw new NotFoundHttpException('Site não existe!');
            }

            $this->apacheService->createUserIniFile($site, $userIni);

            $this->addFlash('success', 'Arquivo de configuração foi criado!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }
}
