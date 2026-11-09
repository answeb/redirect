<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Repository;

use Cache;
use Doctrine\DBAL\Connection;

class RedirectRuleRepository
{
    private const CACHE_KEY_ACTIVE = 'redirect_rule_active_ordered';

    private const TABLE = 'redirect_rule';

    public function __construct(
        private readonly Connection $connection,
        private readonly string $dbPrefix
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findActiveOrdered(): array
    {
        if (Cache::isStored(self::CACHE_KEY_ACTIVE)) {
            return Cache::retrieve(self::CACHE_KEY_ACTIVE);
        }

        $rules = $this->connection->createQueryBuilder()
            ->select('id_redirect_rule, source, destination, is_regex, http_code')
            ->from($this->dbPrefix . self::TABLE)
            ->where('active = 1')
            ->orderBy('position', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        Cache::store(self::CACHE_KEY_ACTIVE, $rules);

        return $rules;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $row = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->dbPrefix . self::TABLE)
            ->where('id_redirect_rule = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        return $row === false ? null : $row;
    }

    public function add(array $data): int
    {
        $this->connection->insert($this->dbPrefix . self::TABLE, [
            'source' => $data['source'],
            'destination' => $data['destination'],
            'is_regex' => (int) $data['is_regex'],
            'http_code' => (int) $data['http_code'],
            'active' => (int) $data['active'],
            'position' => $this->getNextPosition(),
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ]);

        $id = (int) $this->connection->lastInsertId();

        $this->invalidateCache();

        return $id;
    }

    public function update(int $id, array $data): void
    {
        $this->connection->update(
            $this->dbPrefix . self::TABLE,
            [
                'source' => $data['source'],
                'destination' => $data['destination'],
                'is_regex' => (int) $data['is_regex'],
                'http_code' => (int) $data['http_code'],
                'active' => (int) $data['active'],
                'date_upd' => date('Y-m-d H:i:s'),
            ],
            ['id_redirect_rule' => $id]
        );

        $this->invalidateCache();
    }

    public function delete(int $id): void
    {
        $this->connection->delete($this->dbPrefix . self::TABLE, ['id_redirect_rule' => $id]);

        $this->invalidateCache();
    }

    public function toggleActive(int $id): void
    {
        $this->connection->executeStatement(
            'UPDATE `' . $this->dbPrefix . self::TABLE . '` SET active = NOT active, date_upd = :date_upd WHERE id_redirect_rule = :id',
            ['date_upd' => date('Y-m-d H:i:s'), 'id' => $id]
        );

        $this->invalidateCache();
    }

    /**
     * @param array<int, array{source: string, destination: string, http_code: int}> $rows
     */
    public function bulkInsertFromCsv(array $rows): int
    {
        if ([] === $rows) {
            return 0;
        }

        $this->connection->beginTransaction();

        try {
            $position = $this->getNextPosition();
            $now = date('Y-m-d H:i:s');

            foreach ($rows as $row) {
                $this->connection->insert($this->dbPrefix . self::TABLE, [
                    'source' => $row['source'],
                    'destination' => $row['destination'],
                    'is_regex' => 0,
                    'http_code' => $row['http_code'],
                    'active' => 1,
                    'position' => $position++,
                    'date_add' => $now,
                    'date_upd' => $now,
                ]);
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }

        $this->invalidateCache();

        return count($rows);
    }

    private function getNextPosition(): int
    {
        $max = $this->connection->createQueryBuilder()
            ->select('MAX(position)')
            ->from($this->dbPrefix . self::TABLE)
            ->executeQuery()
            ->fetchOne();

        return $max === null ? 0 : ((int) $max) + 1;
    }

    private function invalidateCache(): void
    {
        Cache::clean(self::CACHE_KEY_ACTIVE);
    }
}
