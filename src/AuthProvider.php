<?php

declare(strict_types=1);

namespace Impulse\Auth;

use Impulse\Auth\Contracts\AuthInterface;
use Impulse\Auth\Contracts\UserProviderInterface;
use Impulse\Auth\Exceptions\AuthException;
use Impulse\Auth\Support\AuthConfig;
use Impulse\Auth\Support\EntityAccessor;
use Impulse\Auth\UserProvider\OrmUserProvider;
use Impulse\Core\Container\ImpulseContainer;
use Impulse\Core\Provider\AbstractProvider;

final class AuthProvider extends AbstractProvider
{
    /**
     * @throws \JsonException
     */
    protected function registerServices(ImpulseContainer $container): void
    {
        $container->set(AuthConfig::class, static fn () => AuthConfig::fromConfig());
        $container->set(EntityAccessor::class, static fn (ImpulseContainer $container) => new EntityAccessor($container->get(AuthConfig::class)));
        $container->set(PasswordHasher::class, static fn () => new PasswordHasher());
        $container->set(SessionStore::class, static fn (ImpulseContainer $container) => new SessionStore($container->get(AuthConfig::class)));

        $container->set(UserProviderInterface::class, static function (ImpulseContainer $container) {
            $config = $container->get(AuthConfig::class);
            $providerClass = $config->providerClass();

            if ($providerClass !== null) {
                $provider = $container->has($providerClass)
                    ? $container->get($providerClass)
                    : $container->make($providerClass);

                if (!$provider instanceof UserProviderInterface) {
                    throw new AuthException(sprintf(
                        'Configured auth provider "%s" must implement %s.',
                        $providerClass,
                        UserProviderInterface::class
                    ));
                }

                return $provider;
            }

            $ormServiceId = 'Cycle\ORM\ORMInterface';
            if ($container->has($ormServiceId)) {
                return new OrmUserProvider(
                    $container->get($ormServiceId),
                    $config->entityClass(),
                    $config->identifierField(),
                    $config->idField(),
                );
            }

            throw new AuthException(
                'No auth user provider could be resolved. Either register the DB provider for ORM support ' .
                'or configure "auth.provider" with a class implementing ' . UserProviderInterface::class . '.'
            );
        });

        $container->set(AuthInterface::class, static fn (ImpulseContainer $container) => new Auth(
            $container->get(UserProviderInterface::class),
            $container->get(SessionStore::class),
            $container->get(PasswordHasher::class),
            $container->get(EntityAccessor::class),
            $container->get(AuthConfig::class),
        ));

        $container->set(Auth::class, static fn (ImpulseContainer $container) => $container->get(AuthInterface::class));
        $container->set('auth', static fn (ImpulseContainer $container) => $container->get(AuthInterface::class));
    }

    /**
     * @throws \Exception
     */
    protected function onBoot(ImpulseContainer $container): void
    {
        $container->get(AuthConfig::class);
    }
}
