<?php

declare(strict_types=1);

namespace Impulse\Auth\Tests;

use Impulse\Auth\Auth;
use Impulse\Auth\Exceptions\AuthException;
use Impulse\Auth\PasswordHasher;
use Impulse\Auth\Support\AuthConfig;
use Impulse\Auth\Support\EntityAccessor;
use Impulse\Auth\Tests\Stubs\ArrayUserProvider;
use Impulse\Auth\Tests\Stubs\FakeSessionStore;
use Impulse\Auth\Tests\Stubs\StubUser;
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    public function testAttemptLogsUserInAndExposesCurrentState(): void
    {
        $config = AuthConfig::fromArrays([
            'entity' => StubUser::class,
        ]);
        $hasher = new PasswordHasher();
        $user = new StubUser(7, 'alice@example.test', $hasher->hash('secret'));
        $session = new FakeSessionStore($config);
        $auth = new Auth(
            new ArrayUserProvider([$user]),
            $session,
            $hasher,
            new EntityAccessor($config),
            $config,
        );

        $this->assertTrue($auth->attempt('alice@example.test', 'secret'));
        $this->assertTrue($auth->check());
        $this->assertFalse($auth->guest());
        $this->assertSame(7, $auth->id());
        $this->assertSame($user, $auth->user());
        $this->assertSame(['auth.user_id' => 7], $session->values);
        $this->assertSame(1, $session->startCalls);
        $this->assertSame(1, $session->regenerateCalls);
    }

    public function testAttemptFailsWithInvalidCredentials(): void
    {
        $config = AuthConfig::fromArrays([
            'entity' => StubUser::class,
        ]);
        $hasher = new PasswordHasher();
        $user = new StubUser(7, 'alice@example.test', $hasher->hash('secret'));
        $session = new FakeSessionStore($config);
        $auth = new Auth(
            new ArrayUserProvider([$user]),
            $session,
            $hasher,
            new EntityAccessor($config),
            $config,
        );

        $this->assertFalse($auth->attempt('alice@example.test', 'bad-password'));
        $this->assertFalse($auth->attempt('missing@example.test', 'secret'));
        $this->assertFalse($auth->check());
        $this->assertTrue($auth->guest());
        $this->assertNull($auth->user());
        $this->assertNull($auth->id());
        $this->assertSame([], $session->values);
    }

    public function testLogoutClearsSessionAndRegeneratesIdentifier(): void
    {
        $config = AuthConfig::fromArrays([
            'entity' => StubUser::class,
        ]);
        $hasher = new PasswordHasher();
        $user = new StubUser(7, 'alice@example.test', $hasher->hash('secret'));
        $session = new FakeSessionStore($config);
        $auth = new Auth(
            new ArrayUserProvider([$user]),
            $session,
            $hasher,
            new EntityAccessor($config),
            $config,
        );

        $auth->login($user);
        $auth->logout();

        $this->assertFalse($auth->check());
        $this->assertNull($auth->id());
        $this->assertSame([], $session->values);
        $this->assertSame(2, $session->regenerateCalls);
    }

    public function testClearsStaleSessionWhenStoredUserCannotBeReloaded(): void
    {
        $config = AuthConfig::fromArrays([
            'entity' => StubUser::class,
        ]);
        $session = new FakeSessionStore($config);
        $session->values['auth.user_id'] = 99;

        $auth = new Auth(
            new ArrayUserProvider([]),
            $session,
            new PasswordHasher(),
            new EntityAccessor($config),
            $config,
        );

        $this->assertNull($auth->user());
        $this->assertFalse($auth->check());
        $this->assertArrayNotHasKey('auth.user_id', $session->values);
    }

    public function testLoginRequiresPersistedIdentifier(): void
    {
        $config = AuthConfig::fromArrays([
            'entity' => StubUser::class,
        ]);
        $auth = new Auth(
            new ArrayUserProvider([]),
            new FakeSessionStore($config),
            new PasswordHasher(),
            new EntityAccessor($config),
            $config,
        );

        $this->expectException(AuthException::class);

        $auth->login(new StubUser(null, 'alice@example.test', 'hash'));
    }
}
