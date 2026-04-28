<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\ApiException;
use App\Support\ApiErrorResponse;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if(!$user)
        {
            return ApiErrorResponse::make(
                title: '! غير موثق',
                message: 'المصادقة مطلوبة للوصول الى المورد الذي تحاول استخدامه',
                status: 401
            );
        }

        $userRole = $user->role?->name->value;

        if(!$userRole || !in_array($userRole, $roles, true))
        {
            return ApiErrorResponse::make(
                title: '! غير مصرح',
                message: 'عزيزي المستخدم انت غير مصرح لك بالقيام بهذا الفعل المحدد',
                status: 403
            );
        }
        return $next($request);
    }
}
