<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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

        $this->renderable(function (PostTooLargeException $e, $request) {
            Log::warning('Request rejected: POST size too large', ['content_length' => $request->headers->get('content-length')]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Kích thước tệp quá lớn. Vui lòng sử dụng ảnh nhỏ hơn.'], 413);
            }

            return redirect()->back()->withInput()->withErrors(['image' => 'Kích thước tệp quá lớn. Vui lòng sử dụng ảnh nhỏ hơn hoặc liên hệ admin để tăng giới hạn.']);
        });
    }
}
