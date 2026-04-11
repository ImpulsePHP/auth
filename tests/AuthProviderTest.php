<?php

declare(strict_types=1);

namespace Impulse\Auth\Tests;

use Impulse\Auth\AuthProvider;
use Impulse\Auth\Contracts\AuthInterface;
use Impulse\Auth\Contracts\UserProviderInterface;
use Impulse\Auth\Exceptions\AuthException;
use Impulse\Auth\PasswordHasher;
use Impulse\Auth\SessionStore;
use Impulse\Auth\Support\AuthConfig;
use Impulse\Auth\Tests\Stubs\ConfiguredUserProvider;
use Impulse\Auth\Tests\Stubs\FakeOrm;
use Impulse\Auth\Tests\Stubs\FakeOrmRepository;
use Impulse\Auth\Tests\Stubs\FakeSessionStore;
use Impulse\Auth\Tests\Stubs\StubUser;
use Impulse\Auth\UserProvider\OrmUserProvider;
use Impulse\Core\Bootstrap\Kernel;
use Impulse\Core\Container\ImpulseContainer;
use Impulse\Core\Support\Config;
use PHPUnit\Framework\TestCase;

final class AuthProviderTest extends TestCase
{
    protected function setUp(): void
    {
        Config::reset();
        ConfiguredUserProvider::$users = [];
    }

    protected function tearDown(): void
    {
        Config::reset();
        ConfiguredUserProvider::$users = [];
    }

    /**
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public function testRegistersAuthServiceWithConfiguredUserProvider(): void
    {
        $hasher = new PasswordHasher();
        $user = new StubUser(1, 'jane@example.test', $hasher->hash('secret'));
        ConfiguredUserProvider::$users = [$user];

        Config::set('auth', [
            'entity' => StubUser::class,
            'provider' => ConfiguredUserProvider::class,
        ]);

        $kernel = new Kernel([new AuthProvider()]);
        $container = $kernel->getContainer();
        $container->set(SessionStore::class, static fn (ImpulseContainer $container) => new FakeSessionStore($container->get(AuthConfig::class)));

        $auth = $container->get(AuthInterface::class);

        $this->assertInstanceOf(AuthInterface::class, $auth);
        $this->assertTrue($auth->attempt('jane@example.test', 'secret'));
    }

    /**
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public function testFallsBackToOrmUserProviderWhenOrmServiceExists(): void
    {
        $hasher = new PasswordHasher();
        $user = new StubUser(3, 'orm@example.test', $hasher->hash('secret'));
        $container = new ImpulseContainer();
        $container->set('Cycle\ORM\ORMInterface', static fn () => new FakeOrm(new FakeOrmRepository([$user]), [$user]));

        Config::set('auth', [
            'entity' => StubUser::class,
        ]);

        $kernel = new Kernel([new AuthProvider()], $container);
        $container = $kernel->getContainer();
        $container->set(SessionStore::class, static fn (ImpulseContainer $container) => new FakeSessionStore($container->get(AuthConfig::class)));

        $provider = $container->get(UserProviderInterface::class);
        $auth = $container->get(AuthInterface::class);

        $this->assertInstanceOf(OrmUserProvider::class, $provider);
        $this->assertTrue($auth->attempt('orm@example.test', 'secret'));
        $this->assertSame(3, $auth->id());
    }

    /**
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public function testOrmSelectorBypassesInvalidCustomRepository(): void
    {
        $hasher = new PasswordHasher();
        $user = new StubUser(11, 'selector@example.test', $hasher->hash('secret'));

        $container = new ImpulseContainer();
        $container->set('Cycle\ORM\ORMInterface', static fn () => new FakeOrm(new class {
            public function findOne(array $criteria): never
            {
                throw new \RuntimeException('Repository path should not be used.');
            }
        }, [$user]));

        Config::set('auth', [
            'entity' => StubUser::class,
        ]);

        $kernel = new Kernel([new AuthProvider()], $container);
        $container = $kernel->getContainer();
        $container->set(SessionStore::class, static fn (ImpulseContainer $container) => new FakeSessionStore($container->get(AuthConfig::class)));

        $auth = $container->get(AuthInterface::class);

        $this->assertTrue($auth->attempt('selector@example.test', 'secret'));
        $this->assertSame(11, $auth->id());
    }

    public function testRequiresEntityConfiguration(): void
    {
        $this->expectException(AuthException::class);

        new Kernel([new AuthProvider()]);
    }
}
