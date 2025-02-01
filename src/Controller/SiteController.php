<?php

namespace App\Controller;

use App\Entity\Site;
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
     * @Route("/site", name="app_site_index")
     */
    public function index(): Response
    {
        $sites = $this->siteService->getAll();

        return $this->render('site/index.html.twig', [
            'sites' => $sites,
        ]);
    }

    /**
     * @Route("/site/create", name="app_site_create")
     */
    public function create(Request $request): Response
    {
        try {
            $requestData = $request->request->all();

            $this->siteService->create($requestData);

            $this->addFlash('success', 'Site created!');
        } catch (\Exception $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }

    /**
     * @Route("/site/{id}/edit", name="app_site_edit")
     */
    public function edit(int $id): Response
    {
        try {
            $site = $this->siteService->get($id);
            
            return $this->render('site/edit.html.twig', [
                'site' => $site,
            ]);
        } catch (\Exception $exception) {
            $this->addFlash('error', $exception->getMessage());
            return $this->redirectToRoute('app_site_index');
        }
    }

    /**
     * @Route("/site/{id}/update", name="app_site_update")
     */
    public function update(Request $request, int $id): Response
    {
        try {
            $requestData = $request->request->all();
            $site = $this->siteService->get($id);
            $this->siteService->update($requestData, $site);
        } catch (\Exception $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }
}
