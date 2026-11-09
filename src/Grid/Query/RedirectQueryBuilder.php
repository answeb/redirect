<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Grid\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicatorInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

class RedirectQueryBuilder extends AbstractDoctrineQueryBuilder
{
    public function __construct(
        Connection $connection,
        string $dbPrefix,
        private readonly DoctrineSearchCriteriaApplicatorInterface $searchCriteriaApplicator
    ) {
        parent::__construct($connection, $dbPrefix);
    }

    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->getCommonQueryBuilder($searchCriteria);
        $qb->select('r.*');

        $this->searchCriteriaApplicator
            ->applyPagination($searchCriteria, $qb)
            ->applySorting($searchCriteria, $qb)
        ;

        return $qb;
    }

    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        return $this->getCommonQueryBuilder($searchCriteria)
            ->select('COUNT(r.id_redirect_rule)');
    }

    private function getCommonQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder()
            ->from($this->dbPrefix . 'redirect_rule', 'r')
        ;

        $filtersMap = [
            'id_redirect_rule' => 'r.id_redirect_rule',
            'source' => 'r.source',
            'destination' => 'r.destination',
            'active' => 'r.active',
        ];

        foreach ($searchCriteria->getFilters() as $filterName => $value) {
            if ('' === $value || null === $value || !array_key_exists($filterName, $filtersMap)) {
                continue;
            }

            $column = $filtersMap[$filterName];

            if (in_array($filterName, ['id_redirect_rule', 'active'], true)) {
                $qb->andWhere($column . ' = :' . $filterName)
                    ->setParameter($filterName, $value);

                continue;
            }

            $qb->andWhere($column . ' LIKE :' . $filterName)
                ->setParameter($filterName, '%' . $this->escapePercent((string) $value) . '%');
        }

        return $qb;
    }
}
