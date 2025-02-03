<?php

namespace App\Controller;

use App\Service\ApacheService;
use App\Service\ApacheVirtualHostFileService;
use App\Service\MkcertService;
use App\Service\SiteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

            $this->addFlash('success', 'Arquivos de configuração foram criados com sucesso!');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }
}
