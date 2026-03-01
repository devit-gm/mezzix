<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
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

    function render($request, Throwable $exception)
    {
        // 🚀 Capturar Token CSRF Mismatch y redirigir elegantemente
        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
            \Log::warning('Token CSRF expirado para usuario', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent()
            ]);
            
            return redirect()
                ->route('login')
                ->withInput($request->except(['password', '_token']))
                ->with('error', 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.');
        }
        
        if ($this->isHttpException($exception)) {
            if ($exception->getStatusCode() == 403) {
                return response()->view('errors.403', [], 403);
            }
            if ($exception->getStatusCode() == 404) {
                return response()->view('errors.404', [], 404);
            }
            if ($exception->getStatusCode() == 419) {
                // También redirigir si es 419 HTTP
                return redirect()
                    ->route('login')
                    ->with('error', 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.');
            }
            if ($exception->getStatusCode() == 500) {
                \Log::error('Error 500', [
                    'exception' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'url' => $request->fullUrl()
                ]);
                return response()->view('errors.500', [], 500);
            }
        }
        return parent::render($request, $exception);
    }
}
