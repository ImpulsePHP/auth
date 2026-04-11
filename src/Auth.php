<?php

declare(strict_types=1);

namespace Impulse\Auth;

use Impulse\Auth\Contracts\AuthInterface;
use Impulse\Auth\Contracts\UserProviderInterface;
use Impulse\Auth\Exceptions\AuthException;
use Impulse\Auth\Support\AuthConfig;
use Impulse\Auth\Support\EntityAccessor;

final class Auth implements AuthInterface
{
    private ?object $cachedUser = null;
    private bool $userResolved = false;
    private static ?string $dummyHash = null;

    public function __construct(
        private readonly UserProviderInterface $users,
        private readonly SessionStore $session,
        private readonly PasswordHasher $hasher,
        private readonly EntityAccessor $accessor,
        private readonly AuthConfig $config,
    ) {
    }

    public function attempt(string $identifier, string $password): bool
    {
        $user = $this->users->findByIdentifier($identifier);
        if (!is_object($user)) {
            $this->fakeVerify($password);

            return false;
        }

        $hash = $this->accessor->passwordHash($user);
        if ($hash === null) {
            $this->fakeVerify($password);

            return false;
        }

        if (!$this->hasher->verify($password, $hash)) {
            return false;
        }

        $this->login($user);

        return true;
    }

    public function login(object $user): void
    {
        $id = $this->accessor->id($user);
        if ($id === null || $id === '') {
            throw new AuthException(sprintf(
                'Cannot login "%s" because the configured id field "%s" resolved to null.',
                $user::class,
                $this->config->idField()
            ));
        }

        $this->session->start();
        $this->session->regenerate();
        $this->session->set($this->config->sessionKey(), $id);

        $this->cachedUser = $user;
        $this->userResolved = true;
    }

    public function logout(): void
    {
        $this->session->remove($this->config->sessionKey());
        $this->session->regenerate();

        $this->cachedUser = null;
        $this->userResolved = true;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?object
    {
        if ($this->userResolved) {
            return $this->cachedUser;
        }

        $this->userResolved = true;

        $id = $this->session->get($this->config->sessionKey());
        if ($id === null || $id === '') {
            return null;
        }

        $user = $this->users->findById($id);
        if (!is_object($user)) {
            $this->session->remove($this->config->sessionKey());

            return null;
        }

        $this->cachedUser = $user;

        return $this->cachedUser;
    }

    public function id(): int|string|null
    {
        if ($this->userResolved) {
            return $this->cachedUser ? $this->accessor->id($this->cachedUser) : null;
        }

        $id = $this->session->get($this->config->sessionKey());

        return is_int($id) || is_string($id) ? $id : null;
    }

    private function fakeVerify(string $password): void
    {
        self::$dummyHash ??= $this->hasher->hash('impulse-auth-dummy-password');
        $this->hasher->verify($password, self::$dummyHash);
    }
}
