<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PhpVersionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PhpVersionController extends AbstractController
{
    private PhpVersionService $phpVersionService;

    private TranslatorInterface $translator;

    public function __construct(
        PhpVersionService $phpVersionService,
        TranslatorInterface $translator
    ) {
        $this->phpVersionService = $phpVersionService;
        $this->translator = $translator;
    }

    /**
     * @Route("/phpversion", name="app_phpversion_index", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);

        $pagination = $this->phpVersionService->getAll($page);

        $status = $this->phpVersionService->getStatus();
        
        return $this->render('php_version/index.html.twig', [
            'pagination' => $pagination,
            'status' => $status,
        ]);
    }

    /**
     * @Route("/phpversion/update", name="app_phpversion_update", methods={"GET"})
     */
    public function update(): Response
    {
        try {
            $this->phpVersionService->update();

            $this->addFlash('success', $this->translator->trans('phpversion.versionsList'));
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_phpversion_index');
    }

    /**
     * @Route("/phpversion/{id}/start", name="app_phpversion_start", methods={"GET"})
     */
    public function start(int $id): Response
    {
        try {
            $this->phpVersionService->changeStatus($id, 'start');

            $this->addFlash('success', $this->translator->trans('phpversion.start'));
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_phpversion_index');
    }

    /**
     * @Route("/phpversion/{id}/restart", name="app_phpversion_restart", methods={"GET"})
     */
    public function restart(int $id): Response
    {
        try {
            $this->phpVersionService->changeStatus($id, 'restart');

            $this->addFlash('success', $this->translator->trans('phpversion.restart'));
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_phpversion_index');
    }

    /**
     * @Route("/phpversion/{id}/stop", name="app_phpversion_stop", methods={"GET"})
     */
    public function stop(int $id): Response
    {
        try {
            $this->phpVersionService->changeStatus($id, 'stop');

            $this->addFlash('success', $this->translator->trans('phpversion.stop'));
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_phpversion_index');
    }

    /**
     * @Route("/phpversion/{id}/edit-ini", name="app_phpversion_edit_ini", methods={"GET"})
     */
    public function editIni(int $id): Response
    {
        try {
            $phpVersion = $this->phpVersionService->get($id);

            return $this->render('php_version/edit.html.twig', [
                'entity' => $phpVersion,
                'configFileDto' => $this->phpVersionService->getIni($phpVersion),
            ]);
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('app_phpversion_index');
        }
    }

    /**
     * @Route("/phpversion/{id}/update-ini", name="app_phpversion_update_ini", methods={"POST"})
     */
    public function updateIni(Request $request, int $id): Response
    {
        try {
            $content = $request->request->get('content');

            $this->phpVersionService->updateIni($id, $content);

            $this->addFlash('success', $this->translator->trans('fileHasUpdated'));
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_phpversion_index');
    }
}
