<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows API / GraphQL clients to maintain a session via an HTTP header
 * instead of a cookie. The client sends:
 *
 *   X-Session-Token: <token>
 *
 * and receives the same token echoed back in every response:
 *
 *   X-Session-Token: <token>
 *
 * If no token is provided a new session is started and its ID returned.
 * The client should persist the token and send it with every subsequent request.
 */
class TokenSessionMiddleware
{
    public function __construct(
        private readonly SessionManager $sessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Build a session store manually (bypasses the cookie-based StartSession).
        $session = $this->sessions->driver();

        $token = $request->header('X-Session-Token');

        if ($token && $this->isValidToken($token)) {
            $session->setId($token);
        }

        $session->start();

        // Bind the session to the request so request()->session() works everywhere.
        $request->setLaravelSession($session);

        // Also register the exact same instance in the container so that any code
        // reaching the session via app('session.store') or app('session') gets the
        // same already-started Store — not a new driver() instance with empty data.
        app()->instance('session.store', $session);
        app()->instance('session', $session);

        $response = $next($request);

        // Persist the session to the store (Redis/file/database).
        $session->save();

        // Echo the token back so clients can read/store it.
        $response->headers->set('X-Session-Token', $session->getId());
        $response->headers->set(
            'Access-Control-Expose-Headers',
            ltrim(
                ($response->headers->get('Access-Control-Expose-Headers', '') . ',X-Session-Token'),
                ','
            )
        );

        return $response;
    }

    private function isValidToken(string $token): bool
    {
        // Laravel session IDs are 40-character alphanumeric strings (ctype_alnum).
        // Do NOT restrict to hex-only ([a-f0-9]) — Str::random() produces [A-Za-z0-9].
        return strlen($token) === 40 && ctype_alnum($token);
    }
}

