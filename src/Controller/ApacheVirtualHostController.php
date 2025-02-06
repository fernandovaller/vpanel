<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ApacheService;
use App\Service\ApacheVirtualHostService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApacheVirtualHostController extends AbstractController
{
    private ApacheService $apacheService;

    private ApacheVirtualHostService $apacheVirtualHostService;

    private TranslatorInterface $translator;

    public function __construct(
        ApacheService $apacheService,
        ApacheVirtualHostService $apacheVirtualHostService,
        TranslatorInterface $translator
    ) {
        $this->apacheService = $apacheService;
        $this->apacheVirtualHostService = $apacheVirtualHostService;
        $this->translator = $translator;
    }

    /**
     * @Route("/apache/virtualhost/{id}/create", name="app_apache_virtualhost_create", methods={"GET"})
     */
    public function create(int $id): Response
    {
        try {
            $this->apacheService->create($id);

            $this->addFlash('success', $this->translator->trans('fileHasCreated'));
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

            $this->addFlash('success', $this->translator->trans('site.form.deleted'));
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

            $this->addFlash('success', $this->translator->trans('fileHasUpdated'));
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_site_index');
    }
}
