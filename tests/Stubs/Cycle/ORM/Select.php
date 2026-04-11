<?php

declare(strict_types=1);

namespace Cycle\ORM;

final class Select
{
    public function __construct(
        private readonly object $orm,
        private readonly string $entityClass,
    ) {
    }

    public function fetchOne(?array $criteria = null): ?object
    {
        if (!method_exists($this->orm, 'selectOne')) {
            return null;
        }

        return $this->orm->selectOne($this->entityClass, $criteria ?? []);
    }
}
