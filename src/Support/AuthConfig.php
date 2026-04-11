<?php

declare(strict_types=1);

namespace Impulse\Auth\Support;

use Impulse\Auth\Exceptions\AuthException;
use Impulse\Core\Support\Config;

final readonly class AuthConfig
{
    public function __construct(
        private string $entityClass,
        private string $identifierField = 'email',
        private string $passwordField = 'password',
        private string $idField = 'id',
        private string $sessionKey = 'auth.user_id',
        private string $loginPath = '/',
        private ?string $providerClass = null,
        private string $sessionCookie = 'impulse_session',
        private int $sessionLifetime = 1200,
        private string $sessionPath = '/',
        private string $sessionDomain = '',
        private ?bool $sessionSecure = null,
        private bool $sessionHttpOnly = true,
        private string $sessionSameSite = 'Lax',
    ) {
    }

    /**
     * @throws \JsonException
     */
    public static function fromConfig(): self
    {
        $auth = Config::get('auth', []);
        $session = Config::get('session', []);

        if (!is_array($auth)) {
            throw new AuthException('The "auth" configuration must be an array.');
        }

        if (!is_array($session)) {
            throw new AuthException('The "session" configuration must be an array.');
        }

        return self::fromArrays($auth, $session);
    }

    public static function fromArrays(array $auth, array $session = []): self
    {
        $entityClass = $auth['entity'] ?? null;
        if (!is_string($entityClass) || $entityClass === '') {
            throw new AuthException('The "auth.entity" configuration is required and must be a class-string.');
        }

        if (!class_exists($entityClass)) {
            throw new AuthException(sprintf('Configured auth entity "%s" does not exist.', $entityClass));
        }

        $providerClass = $auth['provider'] ?? null;
        if ($providerClass !== null && (!is_string($providerClass) || $providerClass === '')) {
            throw new AuthException('The "auth.provider" configuration must be a non-empty class-string when set.');
        }

        if (is_string($providerClass) && !class_exists($providerClass)) {
            throw new AuthException(sprintf('Configured auth provider "%s" does not exist.', $providerClass));
        }

        return new self(
            entityClass: $entityClass,
            identifierField: self::stringOrDefault($auth['identifier_field'] ?? null, 'email'),
            passwordField: self::stringOrDefault($auth['password_field'] ?? null, 'password'),
            idField: self::stringOrDefault($auth['id_field'] ?? null, 'id'),
            sessionKey: self::stringOrDefault($auth['session_key'] ?? null, 'auth.user_id'),
            loginPath: self::stringOrDefault($auth['login_path'] ?? null, '/'),
            providerClass: $providerClass,
            sessionCookie: self::stringOrDefault($session['cookie'] ?? null, 'impulse_session'),
            sessionLifetime: self::intOrDefault($session['lifetime'] ?? null, 1200),
            sessionPath: self::stringOrDefault($session['path'] ?? null, '/'),
            sessionDomain: self::stringOrDefault($session['domain'] ?? null, ''),
            sessionSecure: self::nullableBool($session['secure'] ?? null),
            sessionHttpOnly: self::boolOrDefault($session['http_only'] ?? null, true),
            sessionSameSite: self::stringOrDefault($session['same_site'] ?? null, 'Lax'),
        );
    }

    public function entityClass(): string
    {
        return $this->entityClass;
    }

    public function identifierField(): string
    {
        return $this->identifierField;
    }

    public function passwordField(): string
    {
        return $this->passwordField;
    }

    public function idField(): string
    {
        return $this->idField;
    }

    public function sessionKey(): string
    {
        return $this->sessionKey;
    }

    public function loginPath(): string
    {
        return $this->loginPath;
    }

    public function providerClass(): ?string
    {
        return $this->providerClass;
    }

    public function sessionCookie(): string
    {
        return $this->sessionCookie;
    }

    public function sessionLifetime(): int
    {
        return $this->sessionLifetime;
    }

    public function sessionPath(): string
    {
        return $this->sessionPath;
    }

    public function sessionDomain(): string
    {
        return $this->sessionDomain;
    }

    public function sessionSecure(): ?bool
    {
        return $this->sessionSecure;
    }

    public function sessionHttpOnly(): bool
    {
        return $this->sessionHttpOnly;
    }

    public function sessionSameSite(): string
    {
        return $this->sessionSameSite;
    }

    private static function stringOrDefault(mixed $value, string $default): string
    {
        return is_string($value) && $value !== '' ? $value : $default;
    }

    private static function intOrDefault(mixed $value, int $default): int
    {
        return is_int($value) && $value >= 0 ? $value : $default;
    }

    private static function boolOrDefault(mixed $value, bool $default): bool
    {
        return is_bool($value) ? $value : $default;
    }

    private static function nullableBool(mixed $value): ?bool
    {
        return is_bool($value) ? $value : null;
    }
}
