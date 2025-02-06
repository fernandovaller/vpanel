<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ApacheService;
use App\Service\SiteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
            $this->apacheService->start();

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
            $this->apacheService->stop();

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
            $this->apacheService->restart();

            $this->addFlash('success', 'Apache restarted successfully.!');
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
            $content = $request->request->get('userIni');

            $this->apacheService->updateUserIniFile($id, $content);

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

            $this->apacheService->updateFpmPoolFile($id, $content);

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
