<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Support\ApiErrorResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    public function refresh()
    {
        $newToken = JWTAuth::parseToken()->refresh();
        return response()->json([
            'success' => true,
            'newToken' => $newToken,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }
}
