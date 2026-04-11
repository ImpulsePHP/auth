<?php

declare(strict_types=1);

namespace Impulse\Auth\Tests\Stubs;

final class StubUser
{
    public function __construct(
        private int|string|null $id,
        private ?string $email,
        private ?string $password,
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
}
