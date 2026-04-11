<?php

declare(strict_types=1);

namespace Impulse\Auth\Tests\Stubs;

use Impulse\Auth\Contracts\UserProviderInterface;

final class ArrayUserProvider implements UserProviderInterface
{
    /**
     * @param list<object> $users
     */
    public function __construct(private array $users)
    {
    }

    public function findByIdentifier(string $identifier): ?object
    {
        foreach ($this->users as $user) {
            if (method_exists($user, 'getEmail') && $user->getEmail() === $identifier) {
                return $user;
            }
        }

        return null;
    }

    public function findById(int|string $id): ?object
    {
        foreach ($this->users as $user) {
            if (method_exists($user, 'getId') && $user->getId() === $id) {
                return $user;
            }
        }

        return null;
    }
}
