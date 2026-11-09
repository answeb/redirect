<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Grid\Definition\Factory;

use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\BulkActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\BulkActionCollectionInterface;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollectionInterface;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\BulkActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\PositionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ToggleColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractFilterableGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\BulkDeleteActionTrait;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\DeleteActionTrait;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollectionInterface;
use PrestaShopBundle\Form\Admin\Type\ReorderPositionsButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RedirectGridDefinitionFactory extends AbstractFilterableGridDefinitionFactory
{
    use DeleteActionTrait;
    use BulkDeleteActionTrait;

    public const GRID_ID = 'redirect_rule';

    protected function getId(): string
    {
        return self::GRID_ID;
    }

    protected function getName(): string
    {
        return $this->trans('Redirections', [], 'Modules.Redirect.Admin');
    }

    protected function getColumns(): ColumnCollectionInterface
    {
        return (new ColumnCollection())
            ->add((new BulkActionColumn('bulk'))
                ->setOptions([
                    'bulk_field' => 'id_redirect_rule',
                ])
            )
            ->add((new DataColumn('source'))
                ->setName($this->trans('Source', [], 'Modules.Redirect.Admin'))
                ->setOptions(['field' => 'source'])
            )
            ->add((new DataColumn('destination'))
                ->setName($this->trans('Destination', [], 'Modules.Redirect.Admin'))
                ->setOptions(['field' => 'destination'])
            )
            ->add((new DataColumn('http_code'))
                ->setName($this->trans('Code HTTP', [], 'Modules.Redirect.Admin'))
                ->setOptions(['field' => 'http_code'])
            )
            ->add((new ToggleColumn('active'))
                ->setName($this->trans('Active', [], 'Admin.Global'))
                ->setOptions([
                    'field' => 'active',
                    'primary_field' => 'id_redirect_rule',
                    'route' => 'admin_redirect_toggle_active',
                    'route_param_name' => 'redirectRuleId',
                ])
            )
            ->add((new PositionColumn('position'))
                ->setName($this->trans('Position', [], 'Admin.Global'))
                ->setOptions([
                    'id_field' => 'id_redirect_rule',
                    'position_field' => 'position',
                    'update_method' => 'POST',
                    'update_route' => 'admin_redirect_update_position',
                ])
            )
            ->add((new ActionColumn('actions'))
                ->setName($this->trans('Actions', [], 'Admin.Global'))
                ->setOptions([
                    'actions' => (new RowActionCollection())
                        ->add((new LinkRowAction('edit'))
                            ->setName($this->trans('Edit', [], 'Admin.Actions'))
                            ->setIcon('edit')
                            ->setOptions([
                                'route' => 'admin_redirect_edit',
                                'route_param_name' => 'redirectRuleId',
                                'route_param_field' => 'id_redirect_rule',
                                'clickable_row' => true,
                            ])
                        )
                        ->add($this->buildDeleteAction(
                            'admin_redirect_delete',
                            'redirectRuleId',
                            'id_redirect_rule'
                        )),
                ])
            )
        ;
    }

    protected function getFilters(): FilterCollectionInterface
    {
        return (new FilterCollection())
            ->add((new Filter('source', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                    'attr' => ['placeholder' => $this->trans('Recherche source', [], 'Modules.Redirect.Admin')],
                ])
                ->setAssociatedColumn('source')
            )
            ->add((new Filter('destination', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                    'attr' => ['placeholder' => $this->trans('Recherche destination', [], 'Modules.Redirect.Admin')],
                ])
                ->setAssociatedColumn('destination')
            )
            ->add((new Filter('active', ChoiceType::class))
                ->setTypeOptions([
                    'required' => false,
                    'choices' => [
                        $this->trans('Yes', [], 'Admin.Global') => 1,
                        $this->trans('No', [], 'Admin.Global') => 0,
                    ],
                ])
                ->setAssociatedColumn('active')
            )
            ->add((new Filter('position', ReorderPositionsButtonType::class))
                ->setAssociatedColumn('position')
            )
        ;
    }

    protected function getBulkActions(): BulkActionCollectionInterface
    {
        return (new BulkActionCollection())
            ->add($this->buildBulkDeleteAction('admin_redirect_bulk_delete'));
    }
}
