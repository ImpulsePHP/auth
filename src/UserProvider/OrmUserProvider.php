<?php

declare(strict_types=1);

namespace Impulse\Auth\UserProvider;

use Impulse\Auth\Contracts\UserProviderInterface;
use Impulse\Auth\Exceptions\AuthException;

final readonly class OrmUserProvider implements UserProviderInterface
{
    public function __construct(
        private object $orm,
        private string $entityClass,
        private string $identifierField,
        private string $idField,
    ) {
    }

    public function findByIdentifier(string $identifier): ?object
    {
        $user = $this->selectOne([$this->identifierField => $identifier]);

        return is_object($user) ? $user : null;
    }

    public function findById(int|string $id): ?object
    {
        $user = $this->selectOne([$this->idField => $id]);

        return is_object($user) ? $user : null;
    }

    private function selectOne(array $criteria): ?object
    {
        $selectClass = 'Cycle\\ORM\\Select';
        if (class_exists($selectClass)) {
            $select = new $selectClass($this->orm, $this->entityClass);

            if (!method_exists($select, 'fetchOne')) {
                throw new AuthException(sprintf(
                    'The ORM selector for "%s" must expose a fetchOne(?array $criteria) method.',
                    $this->entityClass
                ));
            }

            $user = $select->fetchOne($criteria);

            return is_object($user) ? $user : null;
        }

        return $this->selectThroughRepository($criteria);
    }

    private function selectThroughRepository(array $criteria): ?object
    {
        if (!method_exists($this->orm, 'getRepository')) {
            throw new AuthException(
                'The configured ORM service must expose either Cycle\\ORM\\Select or a getRepository() method.'
            );
        }

        $repository = $this->orm->getRepository($this->entityClass);

        if (!is_object($repository)) {
            throw new AuthException(sprintf('Unable to resolve an ORM repository for "%s".', $this->entityClass));
        }

        if (!method_exists($repository, 'findOne')) {
            throw new AuthException(sprintf(
                'The ORM repository for "%s" must expose a findOne(array $criteria) method.',
                $this->entityClass
            ));
        }

        $user = $repository->findOne($criteria);

        return is_object($user) ? $user : null;
    }
}
