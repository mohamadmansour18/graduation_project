<?php

namespace App\Http\Middleware;

use App\Exceptions\Jwt\RefreshTokenExpiredException;
use App\Exceptions\Jwt\TokenMissingException;
use App\Services\Auth\AuthService;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class JwtAuthMiddleware
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        // is token exists ?
        $token = $request->bearerToken();

        if (! $token) {
            throw new TokenMissingException();
        }

        // is token correct and not expired ? (catch in handler : 1- TokenInvalidException "modified or invalid" , 2- TokenExpiredException "access ttl expired" , 3- JWTException "There is general problem")
        $user = JWTAuth::setToken($token)->authenticate();

        if (! $user) {
            throw new TokenMissingException();
        }

        $this->authService->assertUserIsNotBanned($user);

        auth()->setUser($user);

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

}
