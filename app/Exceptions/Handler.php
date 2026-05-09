<?php

namespace App\Exceptions;

use App\Exceptions\Api\ApiException;
use App\Support\ApiErrorResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Lottery;
use Illuminate\Validation\ValidationException;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use App\Exceptions\Jwt\TokenMissingException;
use App\Exceptions\Jwt\RefreshTokenExpiredException;
use Illuminate\Contracts\Cache\LockTimeoutException;

class Handler extends ExceptionHandler
{
    protected $withoutDuplicates = true;

    protected $levels = [
        QueryException::class => LogLevel::CRITICAL,
    ];

    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        MethodNotAllowedHttpException::class,
        TooManyRequestsHttpException::class,
        ApiException::class,
        TokenExpiredException::class,
        TokenInvalidException::class,
        JWTException::class,
    ];

    protected function context(): array
    {
        $request = request();

        return array_merge(parent::context(), [
            'request_id' => $request?->attributes->get('request_id'),
            'path' => $request?->path(),
            'method' => $request?->method(),
        ]);
    }

    protected function throttle(Throwable $e): mixed
    {
        return match (true) {
            $e instanceof NotFoundHttpException => Lottery::odds(1, 1000),
            $e instanceof ValidationException => Lottery::odds(1, 100),
            default => null,
        };
    }

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if (! $request->is('api/*')) {
            return parent::render($request, $e);
        }

        return match (true) {
            $e instanceof ValidationException => ApiErrorResponse::make(
                title: '! خطأ تحقق',
                message: $e->validator->errors()->first(),
                status: 422
            ),

            $e instanceof TokenMissingException => ApiErrorResponse::make(
                title: '! التوكن مفقود',
                message: 'يرجى إرسال رمز الوصول ايها الفرونت الاحمق',
                status: 401
            ),

            $e instanceof TokenInvalidException => ApiErrorResponse::make(
                title: '! توكن غير صالح',
                message: 'رمز الوصول المرسل غير صالح أو تعرض للتعديل',
                status: 401
            ),

            $e instanceof TokenExpiredException => $request->is('api/v1/auth/refresh')
                ? ApiErrorResponse::make(
                    title: '! انتهت جلسة المصادقة',
                    message: 'انتهت مهلة تجديد التوكن، يرجى تسجيل الدخول من جديد',
                    status: 401
                )
                : ApiErrorResponse::make(
                    title: '! انتهت صلاحية التوكن',
                    message: 'انتهت صلاحية رمز الوصول الحالي، يرجى استخدام refresh للحصول على توكن جديد',
                    status: 401
                ),

            $e instanceof JWTException => ApiErrorResponse::make(
                title: '! مشكلة في التوكن',
                message: 'التوكن مفقود أو لا يمكن التعامل معه بشكل صحيح',
                status: 401
            ),

            $e instanceof AuthorizationException => ApiErrorResponse::make(
                title: '! غير مصرح',
                message: 'عزيزي المستخدم انت غير مصرح لك بالقيام بهذا الفعل المحدد',
                status: 403
            ),

            $e instanceof ModelNotFoundException , $e instanceof NotFoundHttpException => ApiErrorResponse::make(
                title: '! غير موجود',
                message: 'المورد الذي تحاول الوصول اليه غير موجود اصلا',
                status: 404
            ),

            $e instanceof MethodNotAllowedHttpException => ApiErrorResponse::make(
                title: '! خطأ في نوع الطلب',
                message: 'النوع الذي تدخله للطلب غير متوافق مع طبيعة عمل الطلب الأساسية',
                status: 405
            ),

            $e instanceof TooManyRequestsHttpException => ApiErrorResponse::make(
                title: '! محاولات كثيرة',
                message: 'لقد قمت بإرسال العديد من الطلبات في وقت قصير ، يرجى المحاولة لاحقا',
                status: 429
            ),

            $e instanceof LockTimeoutException => ApiErrorResponse::make(
                title: '! الطلب قيد المعالجة',
                message: 'يتم تجهيز الطلب حاليا، يرجى إعادة المحاولة بعد لحظات',
                status: 423
            ),

            $e instanceof ApiException => ApiErrorResponse::make(
                title: $e->getTitle(),
                message: $e->getMessage(),
                status: $e->getStatus(),
                meta: $e->getContext(),
            ),

            $e instanceof HttpExceptionInterface => ApiErrorResponse::make(
                title: 'HTTP error.',
                message: $e->getMessage() ?: 'An HTTP error occurred.',
                status: $e->getStatusCode()
            ),

            default => ApiErrorResponse::make(
                title: 'Server error',
                message: config('app.debug')
                    ? $e->getMessage()
                    : 'An unexpected internal server error occurred.',
                status: 500
            ),

        };
    }
}
