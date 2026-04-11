<?php

declare(strict_types=1);

namespace Impulse\Auth\Tests\Stubs;

use Impulse\Auth\SessionStore;
use Impulse\Auth\Support\AuthConfig;

final class FakeSessionStore extends SessionStore
{
    public int $startCalls = 0;
    public int $regenerateCalls = 0;

    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    public function __construct(AuthConfig $config)
    {
        parent::__construct($config);
    }

    public function start(): void
    {
        $this->startCalls++;
    }

    public function regenerate(): void
    {
        $this->regenerateCalls++;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->values[$key]);
    }
}
