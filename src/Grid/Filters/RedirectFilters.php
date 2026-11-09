<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Grid\Filters;

use PrestaShop\Module\Redirect\Grid\Definition\Factory\RedirectGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Search\Filters;

final class RedirectFilters extends Filters
{
    protected $filterId = RedirectGridDefinitionFactory::GRID_ID;

    public static function getDefaults(): array
    {
        return [
            'limit' => 50,
            'offset' => 0,
            'orderBy' => 'position',
            'sortOrder' => 'asc',
            'filters' => [],
        ];
    }
}
