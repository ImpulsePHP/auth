<?php

declare(strict_types=1);

namespace Impulse\Auth\Tests\Middleware;

use Impulse\Auth\Contracts\AuthInterface;
use Impulse\Auth\Middleware\RequireAuthMiddleware;
use Impulse\Auth\Support\AuthConfig;
use Impulse\Auth\Tests\Stubs\StubUser;
use Impulse\Core\Http\Request;
use Impulse\Core\Http\Response;
use PHPUnit\Framework\TestCase;

final class RequireAuthMiddlewareTest extends TestCase
{
    public function testRedirectsGuestToConfiguredLoginPath(): void
    {
        $middleware = new RequireAuthMiddleware(
            $this->guestAuth(),
            AuthConfig::fromArrays([
                'entity' => StubUser::class,
                'login_path' => '/login',
            ])
        );

        $response = $middleware->handle(
            new Request('/account'),
            static fn () => Response::html('ok')
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaders()['Location'] ?? null);
    }

    public function testReturns401JsonForAjaxGuests(): void
    {
        $middleware = new RequireAuthMiddleware(
            $this->guestAuth(),
            AuthConfig::fromArrays([
                'entity' => StubUser::class,
            ])
        );

        $response = $middleware->handle(
            new Request('/impulse.php', 'POST', [], [], [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ]),
            static fn () => Response::html('ok')
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type'] ?? null);
    }

    public function testLetsAuthenticatedRequestsPass(): void
    {
        $middleware = new RequireAuthMiddleware(
            $this->authenticatedAuth(),
            AuthConfig::fromArrays([
                'entity' => StubUser::class,
            ])
        );

        $response = $middleware->handle(
            new Request('/account'),
            static fn () => Response::html('ok', 200)
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    private function guestAuth(): AuthInterface
    {
        return new class implements AuthInterface {
            public function attempt(string $identifier, string $password): bool
            {
                return false;
            }

            public function login(object $user): void
            {
            }

            public function logout(): void
            {
            }

            public function check(): bool
            {
                return false;
            }

            public function guest(): bool
            {
                return true;
            }

            public function user(): ?object
            {
                return null;
            }

            public function id(): int|string|null
            {
                return null;
            }
        };
    }

    private function authenticatedAuth(): AuthInterface
    {
        return new class implements AuthInterface {
            public function attempt(string $identifier, string $password): bool
            {
                return true;
            }

            public function login(object $user): void
            {
            }

            public function logout(): void
            {
            }

            public function check(): bool
            {
                return true;
            }

            public function guest(): bool
            {
                return false;
            }

            public function user(): ?object
            {
                return new StubUser(1, 'john@example.test', 'hash');
            }

            public function id(): int|string|null
            {
                return 1;
            }
        };
    }
}
