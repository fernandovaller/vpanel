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
     * @Route("/apache/info", name="app_apache_info", methods={"GET"})
     */
    public function info(): Response
    {
        try {
            return $this->render('apache/status.html.twig', [
                'apacheStatus' => $this->apacheService->isRunning(),
                'apacheConf' => $this->apacheService->getApacheConf(),
                'apacheLog' => $this->apacheService->getApacheError(),
            ]);
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/apache/start", name="app_apache_start", methods={"GET"})
     */
    public function start(): Response
    {
        try {
            $this->apacheService->startApache();

            $this->addFlash('success', 'Apache started successfully.!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/apache/stop", name="app_apache_stop", methods={"GET"})
     */
    public function stop(): Response
    {
        try {
            $this->apacheService->stopApache();

            $this->addFlash('success', 'Apache stop successfully.!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/apache/restart", name="app_apache_restart", methods={"GET"})
     */
    public function restart(): Response
    {
        try {
            $this->apacheService->restartApache();

            $this->addFlash('success', 'Apache restarted successfully.!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
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

            $this->addFlash('success', 'Arquivo de configuração foi atualizado!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/apache/{id}/userini", name="app_apache_userini", methods={"POST"})
     */
    public function updateUserIni(Request $request, int $id): Response
    {
        try {
            $userIni = $request->request->get('userIni');

            $site = $this->siteService->get($id);

            if ($site === null) {
                throw new NotFoundHttpException('Site não existe!');
            }

            $this->apacheService->updateUserIniFile($site, $userIni);

            $this->addFlash('success', 'Arquivo de configuração foi atualizado!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/apache/{id}/update-fpmpool", name="app_apache_update_fpmpool", methods={"POST"})
     */
    public function updateFpmPool(Request $request, int $id): Response
    {
        try {
            $content = $request->request->get('fpmPool');

            $site = $this->siteService->get($id);

            if ($site === null) {
                throw new NotFoundHttpException('Site não existe!');
            }

            $this->apacheService->updateFpmPoolFile($site, $content);

            $this->addFlash('success', 'Arquivo de configuração foi atualizado!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/apache/update-conf", name="app_apache_update_conf", methods={"POST"})
     */
    public function updateConf(Request $request): Response
    {
        try {
            $content = $request->request->get('apacheConf');

            $this->apacheService->updateApacheConf($content);

            $this->addFlash('success', 'Arquivo de configuração foi atualizado!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }
}
