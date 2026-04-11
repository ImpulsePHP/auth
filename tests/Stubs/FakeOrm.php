<?php

declare(strict_types=1);

namespace Impulse\Auth\Tests\Stubs;

final class FakeOrm
{
    /**
     * @param list<object> $users
     */
    public function __construct(
        private readonly object $repository,
        private readonly array $users = [],
    )
    {
    }

    public function getRepository(string $entityClass): object
    {
        return $this->repository;
    }

    public function selectOne(string $entityClass, array $criteria): ?object
    {
        $field = array_key_first($criteria);
        $value = $field !== null ? $criteria[$field] : null;

        foreach ($this->users as $user) {
            $getter = 'get' . ucfirst((string) $field);
            if (method_exists($user, $getter) && $user->{$getter}() === $value) {
                return $user;
            }
        }

        return null;
    }
}
