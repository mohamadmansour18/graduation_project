<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Trait\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;
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
