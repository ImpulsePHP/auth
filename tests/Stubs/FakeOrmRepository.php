<?php

declare(strict_types=1);

namespace Impulse\Auth\Tests\Stubs;

final class FakeOrmRepository
{
    /**
     * @param list<object> $users
     */
    public function __construct(private array $users)
    {
    }

    public function findOne(array $criteria): ?object
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

    public function findByPK(int|string $id): ?object
    {
        foreach ($this->users as $user) {
            if (method_exists($user, 'getId') && $user->getId() === $id) {
                return $user;
            }
        }

        return null;
    }
}
