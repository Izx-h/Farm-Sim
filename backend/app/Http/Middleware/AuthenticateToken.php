<?php

namespace App\Http\Middleware;

use App\Models\AuthToken;
use Closure;
use Illuminate\Http\Request;

class AuthenticateToken
{
    /**
     * Require a valid bearer token, mirroring the original `requireAuth`.
     * On success attaches the authenticated user to the request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = AuthToken::resolveUser($this->readBearerToken($request));

        if (! $user) {
            return response()->json([
                'error' => 'Authentication required. Please log in.',
            ], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function readBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return trim(substr($header, 7));
    }
}
