<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Controller\Admin;

use Exception;
use PrestaShop\Module\Redirect\Form\CsvImportType;
use PrestaShop\Module\Redirect\Grid\Filters\RedirectFilters;
use PrestaShop\Module\Redirect\Repository\RedirectRuleRepository;
use PrestaShop\Module\Redirect\Service\CsvImportService;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Builder\FormBuilderInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Handler\FormHandlerInterface;
use PrestaShop\PrestaShop\Core\Grid\GridFactoryInterface;
use PrestaShop\PrestaShop\Core\Grid\Position\Exception\PositionUpdateException;
use PrestaShop\PrestaShop\Core\Grid\Position\PositionDefinition;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Controller\BulkActionsTrait;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectController extends PrestaShopAdminController
{
    use BulkActionsTrait;

    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))")]
    public function indexAction(
        Request $request,
        RedirectFilters $filters,
        #[Autowire(service: 'prestashop.module.redirect.grid.factory')]
        GridFactoryInterface $gridFactory,
    ): Response {
        return $this->render('@Modules/redirect/views/templates/admin/index.html.twig', [
            'redirectGrid' => $this->presentGrid($gridFactory->getGrid($filters)),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
            'layoutTitle' => $this->trans('Redirections', [], 'Modules.Redirect.Admin'),
            'layoutHeaderToolbarBtn' => [
                'add_redirect' => [
                    'href' => $this->generateUrl('admin_redirect_create'),
                    'desc' => $this->trans('Ajouter une redirection', [], 'Modules.Redirect.Admin'),
                    'icon' => 'add_circle_outline',
                ],
                'import_redirect' => [
                    'href' => $this->generateUrl('admin_redirect_import'),
                    'desc' => $this->trans('Importer un CSV', [], 'Modules.Redirect.Admin'),
                    'icon' => 'upload',
                ],
            ],
            'enableSidebar' => true,
        ]);
    }

    #[AdminSecurity("is_granted('create', request.get('_legacy_controller'))", redirectRoute: 'admin_redirect_index')]
    public function createAction(
        Request $request,
        #[Autowire(service: 'prestashop.module.redirect.form.builder')]
        FormBuilderInterface $formBuilder,
        #[Autowire(service: 'prestashop.module.redirect.form.handler')]
        FormHandlerInterface $formHandler,
    ): Response {
        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        try {
            $result = $formHandler->handle($form);

            if (null !== $result->getIdentifiableObjectId()) {
                $this->addFlash('success', $this->trans('Successful creation', [], 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_redirect_index');
            }
        } catch (Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->render('@Modules/redirect/views/templates/admin/form.html.twig', [
            'redirectForm' => $form->createView(),
            'layoutTitle' => $this->trans('Nouvelle redirection', [], 'Modules.Redirect.Admin'),
        ]);
    }

    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))", redirectRoute: 'admin_redirect_index')]
    public function editAction(
        int $redirectRuleId,
        Request $request,
        #[Autowire(service: 'prestashop.module.redirect.form.builder')]
        FormBuilderInterface $formBuilder,
        #[Autowire(service: 'prestashop.module.redirect.form.handler')]
        FormHandlerInterface $formHandler,
    ): Response {
        $form = $formBuilder->getFormFor($redirectRuleId);
        $form->handleRequest($request);

        try {
            $result = $formHandler->handleFor($redirectRuleId, $form);

            if ($result->isSubmitted() && $result->isValid()) {
                $this->addFlash('success', $this->trans('Successful update', [], 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_redirect_index');
            }
        } catch (Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->render('@Modules/redirect/views/templates/admin/form.html.twig', [
            'redirectForm' => $form->createView(),
            'layoutTitle' => $this->trans('Modifier la redirection', [], 'Modules.Redirect.Admin'),
        ]);
    }

    #[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))", redirectRoute: 'admin_redirect_index')]
    public function deleteAction(
        int $redirectRuleId,
        #[Autowire(service: 'prestashop.module.redirect.repository')]
        RedirectRuleRepository $repository,
    ): RedirectResponse {
        $repository->delete($redirectRuleId);
        $this->addFlash('success', $this->trans('Successful deletion', [], 'Admin.Notifications.Success'));

        return $this->redirectToRoute('admin_redirect_index');
    }

    #[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))", redirectRoute: 'admin_redirect_index')]
    public function bulkDeleteAction(
        Request $request,
        #[Autowire(service: 'prestashop.module.redirect.repository')]
        RedirectRuleRepository $repository,
    ): RedirectResponse {
        foreach ($this->getBulkActionIds($request, 'redirect_rule_bulk') as $id) {
            $repository->delete($id);
        }

        $this->addFlash('success', $this->trans('Successful deletion', [], 'Admin.Notifications.Success'));

        return $this->redirectToRoute('admin_redirect_index');
    }

    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))", redirectRoute: 'admin_redirect_index')]
    public function toggleActiveAction(
        int $redirectRuleId,
        #[Autowire(service: 'prestashop.module.redirect.repository')]
        RedirectRuleRepository $repository,
    ): JsonResponse {
        try {
            $repository->toggleActive($redirectRuleId);
        } catch (Exception $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()]);
        }

        return $this->json([
            'status' => true,
            'message' => $this->trans('The status has been successfully updated.', [], 'Admin.Notifications.Success'),
        ]);
    }

    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))", redirectRoute: 'admin_redirect_index')]
    public function updatePositionAction(
        Request $request,
        #[Autowire(service: 'prestashop.module.redirect.grid.position_definition')]
        PositionDefinition $positionDefinition,
    ): RedirectResponse {
        try {
            $this->updateGridPosition($positionDefinition, [
                'positions' => $request->request->all('positions'),
            ]);
            $this->addFlash('success', $this->trans('Successful update', [], 'Admin.Notifications.Success'));
        } catch (PositionUpdateException $e) {
            $this->addFlashErrors([$e->toArray()]);
        }

        return $this->redirectToRoute('admin_redirect_index');
    }

    #[AdminSecurity("is_granted('create', request.get('_legacy_controller'))", redirectRoute: 'admin_redirect_index')]
    public function importAction(
        Request $request,
        #[Autowire(service: 'prestashop.module.redirect.service.csv_import')]
        CsvImportService $csvImportService,
    ): Response {
        $form = $this->container->get('form.factory')->create(CsvImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $form->get('file')->getData();

            $report = $csvImportService->import($file->getPathname());

            if ($report['imported'] > 0) {
                $this->addFlash('success', sprintf(
                    '%d redirection(s) importée(s).',
                    $report['imported']
                ));
            }

            if ([] !== $report['errors']) {
                $this->addFlashErrors($report['errors']);
            }

            return $this->redirectToRoute('admin_redirect_index');
        }

        return $this->render('@Modules/redirect/views/templates/admin/import.html.twig', [
            'importForm' => $form->createView(),
            'layoutTitle' => $this->trans('Importer des redirections', [], 'Modules.Redirect.Admin'),
        ]);
    }
}
