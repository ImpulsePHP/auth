<?php

declare(strict_types=1);

namespace Impulse\Auth\Contracts;

interface UserProviderInterface
{
    public function findByIdentifier(string $identifier): ?object;
    public function findById(int|string $id): ?object;
}
