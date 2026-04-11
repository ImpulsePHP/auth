<?php

declare(strict_types=1);

namespace Impulse\Auth\Middleware;

use Impulse\Auth\Contracts\AuthInterface;
use Impulse\Auth\Support\AuthConfig;
use Impulse\Core\Contracts\MiddlewareInterface;
use Impulse\Core\Http\Request;
use Impulse\Core\Http\Response;

final readonly class RequireAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthInterface $auth,
        private AuthConfig $config,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function handle(Request $request, callable $next): Response
    {
        if ($this->auth->check()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->isAjax()) {
            return Response::json([
                'error' => true,
                'message' => 'Authentification requise',
            ], 401);
        }

        $request->flash('error', 'Authentification requise');

        return Response::redirect($this->config->loginPath());
    }
}
