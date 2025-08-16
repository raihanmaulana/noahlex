<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        
        $this->renderable(function (Throwable $e, $request) {
            if (! $this->wantsJson($request)) {
                return null; 
            }

            
            if ($e instanceof AuthenticationException) {
                return $this->jsonError('ERR_UNAUTHORIZED', 'Unauthorized', 401);
            }

            if ($e instanceof AuthorizationException) {
                return $this->jsonError('ERR_FORBIDDEN', 'Forbidden', 403);
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'errorCode' => 'ERR_VALIDATION',
                    'message'   => 'Validation failed',
                    'data'      => $e->errors(), 
                ], 422);
            }

            if ($e instanceof NotFoundHttpException) {
                return $this->jsonError('ERR_NOT_FOUND', 'Not Found', 404);
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return $this->jsonError('ERR_METHOD_NOT_ALLOWED', 'Method Not Allowed', 405);
            }

            if ($e instanceof ThrottleRequestsException) {
                return $this->jsonError('ERR_TOO_MANY_REQUESTS', 'Too Many Requests', 429);
            }

            if ($e instanceof HttpException) {
                
                $status  = $e->getStatusCode();
                $message = $e->getMessage() ?: 'HTTP Error';
                return $this->jsonError('ERR_HTTP', $message, $status);
            }

            if ($e instanceof QueryException) {
                return $this->jsonError('ERR_DB', 'Database error', 500);
            }

            
            return $this->jsonError('ERR_INTERNAL', 'Oops, service error unexpectedly', 500);
        });
    }

    /**
     * Pastikan unauthenticated untuk API tidak redirect ke login page.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($this->wantsJson($request)) {
            return $this->jsonError('ERR_UNAUTHORIZED', 'Unauthorized', 401);
        }

        
        return redirect()->guest(route('login'));
    }

    /**
     * Helper: tentukan apakah request harus JSON (API).
     */
    private function wantsJson($request): bool
    {
        
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * Helper: bentuk response JSON standar client-mu.
     */
    private function jsonError(string $code, string $message, int $status)
    {
        return response()->json([
            'errorCode' => $code,
            'message'   => $message,
            'data'      => null,
        ], $status, ['Content-Type' => 'application/json']);
    }
}
