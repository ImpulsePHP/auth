<?php

declare(strict_types=1);

namespace Impulse\Auth\Contracts;

interface AuthInterface
{
    public function attempt(string $identifier, string $password): bool;
    public function login(object $user): void;
    public function logout(): void;
    public function check(): bool;
    public function guest(): bool;
    public function user(): ?object;
    public function id(): int|string|null;
}
