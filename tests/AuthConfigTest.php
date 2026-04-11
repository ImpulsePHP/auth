<?php

declare(strict_types=1);

namespace Impulse\Auth\Tests;

use Impulse\Auth\Exceptions\AuthException;
use Impulse\Auth\Support\AuthConfig;
use Impulse\Auth\Tests\Stubs\StubUser;
use Impulse\Core\Support\Config;
use PHPUnit\Framework\TestCase;

final class AuthConfigTest extends TestCase
{
    protected function setUp(): void
    {
        Config::reset();
    }

    protected function tearDown(): void
    {
        Config::reset();
    }

    /**
     * @throws \JsonException
     */
    public function testUsesReasonableDefaults(): void
    {
        Config::set('auth', [
            'entity' => StubUser::class,
        ]);

        $config = AuthConfig::fromConfig();

        $this->assertSame(StubUser::class, $config->entityClass());
        $this->assertSame('email', $config->identifierField());
        $this->assertSame('password', $config->passwordField());
        $this->assertSame('id', $config->idField());
        $this->assertSame('auth.user_id', $config->sessionKey());
        $this->assertSame('/', $config->loginPath());
        $this->assertSame('impulse_session', $config->sessionCookie());
        $this->assertSame(1200, $config->sessionLifetime());
    }

    /**
     * @throws \JsonException
     */
    public function testAppliesOverrides(): void
    {
        Config::set('auth', [
            'entity' => StubUser::class,
            'identifier_field' => 'username',
            'password_field' => 'password_hash',
            'id_field' => 'uuid',
            'session_key' => 'security.user',
            'login_path' => '/login',
        ]);
        Config::set('session', [
            'cookie' => 'app_session',
            'lifetime' => 3600,
            'path' => '/app',
            'domain' => 'example.test',
            'secure' => true,
            'http_only' => false,
            'same_site' => 'Strict',
        ]);

        $config = AuthConfig::fromConfig();

        $this->assertSame('username', $config->identifierField());
        $this->assertSame('password_hash', $config->passwordField());
        $this->assertSame('uuid', $config->idField());
        $this->assertSame('security.user', $config->sessionKey());
        $this->assertSame('/login', $config->loginPath());
        $this->assertSame('app_session', $config->sessionCookie());
        $this->assertSame(3600, $config->sessionLifetime());
        $this->assertSame('/app', $config->sessionPath());
        $this->assertSame('example.test', $config->sessionDomain());
        $this->assertTrue($config->sessionSecure());
        $this->assertFalse($config->sessionHttpOnly());
        $this->assertSame('Strict', $config->sessionSameSite());
    }

    public function testEntityIsRequired(): void
    {
        $this->expectException(AuthException::class);

        AuthConfig::fromArrays([]);
    }
}
