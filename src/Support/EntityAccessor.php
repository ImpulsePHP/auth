<?php

declare(strict_types=1);

namespace Impulse\Auth\Support;

use Impulse\Auth\Exceptions\AuthException;

final readonly class EntityAccessor
{
    public function __construct(private AuthConfig $config)
    {
    }

    public function id(object $user): int|string|null
    {
        $value = $this->readField($user, $this->config->idField(), true);

        if ($value === null || is_int($value) || is_string($value)) {
            return $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        throw new AuthException(sprintf(
            'The field "%s" on "%s" must resolve to an int, string or null.',
            $this->config->idField(),
            $user::class
        ));
    }

    public function passwordHash(object $user): ?string
    {
        $value = $this->readField($user, $this->config->passwordField(), true);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @throws \ReflectionException
     */
    private function readField(object $user, string $field, bool $requiredAccessor): mixed
    {
        $reflection = new \ReflectionClass($user);
        $studly = $this->toStudly($field);
        $camel = lcfirst($studly);

        $methods = array_values(array_unique([
            $field,
            $camel,
            'get' . $studly,
            'is' . $studly,
            'has' . $studly,
        ]));

        foreach ($methods as $method) {
            if (!$reflection->hasMethod($method)) {
                continue;
            }

            $refMethod = $reflection->getMethod($method);
            if ($refMethod->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            return $refMethod->invoke($user);
        }

        if ($reflection->hasProperty($field)) {
            return $reflection->getProperty($field)->getValue($user);
        }

        if ($requiredAccessor) {
            throw new AuthException(sprintf(
                'Unable to read the field "%s" on "%s". Adjust your auth configuration or add an accessor.',
                $field,
                $user::class
            ));
        }

        return null;
    }

    private function toStudly(string $value): string
    {
        $segments = preg_split('/[_\-\s]+/', $value) ?: [$value];

        return implode('', array_map(static fn (string $segment): string => ucfirst($segment), $segments));
    }
}
