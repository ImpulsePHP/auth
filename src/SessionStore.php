<?php

declare(strict_types=1);

namespace Impulse\Auth;

use Impulse\Auth\Support\AuthConfig;

class SessionStore
{
    public function __construct(private readonly AuthConfig $config)
    {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if ($this->config->sessionCookie() !== '') {
            session_name($this->config->sessionCookie());
        }

        $secure = $this->config->sessionSecure() ?? $this->isHttpsRequest();
        $options = [
            'lifetime' => $this->config->sessionLifetime(),
            'path' => $this->config->sessionPath(),
            'domain' => $this->config->sessionDomain(),
            'secure' => $secure,
            'httponly' => $this->config->sessionHttpOnly(),
            'samesite' => $this->config->sessionSameSite(),
        ];

        session_set_cookie_params($options);

        session_start([
            'use_strict_mode' => 1,
            'cookie_httponly' => $this->config->sessionHttpOnly() ? 1 : 0,
            'cookie_secure' => $secure ? 1 : 0,
            'cookie_samesite' => $this->config->sessionSameSite(),
            'cookie_lifetime' => $this->config->sessionLifetime(),
            'gc_maxlifetime' => $this->config->sessionLifetime(),
        ]);
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    private function isHttpsRequest(): bool
    {
        if (PHP_SAPI === 'cli') {
            return false;
        }

        $https = $_SERVER['HTTPS'] ?? null;

        return $https !== null && $https !== '' && strtolower((string) $https) !== 'off';
    }
}
