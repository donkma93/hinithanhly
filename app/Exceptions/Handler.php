<?php

namespace App\Exceptions;

use App\Models\SystemErrorLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [
        //
    ];

    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if ($e instanceof ValidationException || $e instanceof HttpResponseException) {
                return;
            }

            $this->storeSystemErrorLog($e);
        });

        $this->renderable(function (PostTooLargeException $e, $request) {
            Log::warning('Request rejected: POST size too large', [
                'content_length' => $request->headers->get('content-length'),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Kich thuoc tep qua lon. Vui long su dung anh nho hon.',
                ], 413);
            }

            return redirect()->back()->withInput()->withErrors([
                'image' => 'Kich thuoc tep qua lon. Vui long su dung anh nho hon hoac lien he admin de tang gioi han.',
            ]);
        });
    }

    public function render($request, Throwable $e): Response
    {
        if (
            $e instanceof PostTooLargeException
            || $e instanceof AuthenticationException
            || $e instanceof AuthorizationException
            || $e instanceof ValidationException
            || $e instanceof HttpResponseException
        ) {
            return parent::render($request, $e);
        }

        if ($request->expectsJson()) {
            return $this->renderJsonErrorResponse($e);
        }

        $status = $this->resolveStatusCode($e);
        $display = $this->resolveDisplayError($e, $status);

        return response()->view('errors.generic', [
            'status' => $status,
            'title' => $display['title'],
            'message' => $display['message'],
            'errorUuid' => $request->attributes->get('system_error_uuid'),
        ], $status);
    }

    private function storeSystemErrorLog(Throwable $e): void
    {
        try {
            $request = app()->bound('request') ? request() : null;
            $errorUuid = (string) Str::uuid();

            if ($request instanceof Request) {
                $request->attributes->set('system_error_uuid', $errorUuid);
            }

            SystemErrorLog::query()->create([
                'error_uuid' => $errorUuid,
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'status_code' => $this->resolveStatusCode($e),
                'method' => $request?->method(),
                'url' => $request?->fullUrl(),
                'route_name' => $request?->route()?->getName(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'user_id' => $request?->user()?->id,
                'trace' => $e->getTraceAsString(),
                'context' => [
                    'input' => $this->sanitizeInput($request),
                    'headers' => $request ? [
                        'referer' => $request->headers->get('referer'),
                        'content_type' => $request->headers->get('content-type'),
                    ] : null,
                ],
            ]);
        } catch (Throwable $loggingException) {
            Log::error('Failed to persist system error log', [
                'message' => $loggingException->getMessage(),
                'original_exception' => $e::class,
            ]);
        }
    }

    private function sanitizeInput(?Request $request): array
    {
        if (! $request instanceof Request) {
            return [];
        }

        return collect($request->except([
            'current_password',
            'password',
            'password_confirmation',
            '_token',
        ]))
            ->map(function ($value) {
                if (is_scalar($value) || $value === null) {
                    return $value;
                }

                return is_array($value) ? '[array]' : '['.get_debug_type($value).']';
            })
            ->all();
    }

    private function renderJsonErrorResponse(Throwable $e): Response
    {
        $status = $this->resolveStatusCode($e);
        $display = $this->resolveDisplayError($e, $status);

        return response()->json([
            'message' => $display['message'],
            'title' => $display['title'],
            'error_id' => request()->attributes->get('system_error_uuid'),
        ], $status);
    }

    private function resolveStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        if ($e instanceof ModelNotFoundException) {
            return 404;
        }

        if ($e instanceof TokenMismatchException) {
            return 419;
        }

        return 500;
    }

    private function resolveErrorTitle(int $status): string
    {
        return match ($status) {
            403 => 'Ban khong co quyen truy cap',
            404 => 'Khong tim thay noi dung',
            419 => 'Phien lam viec da het han',
            default => 'Khong the xu ly yeu cau',
        };
    }

    private function resolveErrorMessage(int $status): string
    {
        return match ($status) {
            403 => 'Ban khong co quyen thuc hien thao tac nay. Neu can, hay lien he quan tri vien.',
            404 => 'Trang hoac du lieu ban can hien khong ton tai hoac da bi thay doi.',
            419 => 'Phien lam viec da het han. Vui long tai lai trang va thu lai.',
            default => 'Yeu cau cua ban da duoc ghi nhan. He thong da luu ma loi de quan tri vien kiem tra va xu ly.',
        };
    }

    /**
     * @return array{title:string,message:string}
     */
    private function resolveDisplayError(Throwable $e, int $status): array
    {
        if ($status === 404) {
            return [
                'title' => 'Khong tim thay du lieu',
                'message' => 'Noi dung ban vua truy cap khong ton tai, da bi xoa hoac duong dan khong dung.',
            ];
        }

        if ($status === 403) {
            return [
                'title' => 'Khong duoc phep thuc hien',
                'message' => 'Tai khoan hien tai khong co quyen thuc hien thao tac nay.',
            ];
        }

        if ($status === 419) {
            return [
                'title' => 'Phien lam viec da het han',
                'message' => 'Vui long tai lai trang va thu lai thao tac vua roi.',
            ];
        }

        if ($e instanceof QueryException) {
            return [
                'title' => 'Loi du lieu he thong',
                'message' => 'Khong the luu hoac doc du lieu tu he thong luc nay. Vui long thu lai sau.',
            ];
        }

        $runtimeMessageMap = [
            'Uploaded image temporary file is missing.' => [
                'title' => 'Khong tim thay anh tai len',
                'message' => 'Anh tai len tam thoi da bi mat truoc khi he thong kip xu ly. Vui long chon anh va thu lai.',
            ],
            'Khong tim thay file anh tam thoi de xu ly.' => [
                'title' => 'Khong tim thay anh tai len',
                'message' => 'Anh tai len tam thoi da bi mat truoc khi he thong kip xu ly. Vui long chon anh va thu lai.',
            ],
            'Unable to open uploaded image temporary file for reading.' => [
                'title' => 'Khong the doc anh tai len',
                'message' => 'He thong khong the doc anh vua tai len. Vui long thu lai hoac chon anh khac.',
            ],
            'Khong the doc file anh vua tai len.' => [
                'title' => 'Khong the doc anh tai len',
                'message' => 'He thong khong the doc anh vua tai len. Vui long thu lai hoac chon anh khac.',
            ],
            'Failed to store uploaded image on public disk.' => [
                'title' => 'Khong the luu anh san pham',
                'message' => 'He thong khong the luu anh san pham vao bo nho. Vui long thu lai sau.',
            ],
            'Khong the luu anh san pham vao he thong.' => [
                'title' => 'Khong the luu anh san pham',
                'message' => 'He thong khong the luu anh san pham vao bo nho. Vui long thu lai sau.',
            ],
        ];

        if (isset($runtimeMessageMap[$e->getMessage()])) {
            return $runtimeMessageMap[$e->getMessage()];
        }

        $message = trim($e->getMessage());

        if ($this->isSafeUserFacingMessage($message)) {
            return [
                'title' => $this->resolveTitleFromException($e),
                'message' => $message,
            ];
        }

        return [
            'title' => $this->resolveTitleFromException($e),
            'message' => $this->resolveErrorMessage($status),
        ];
    }

    private function resolveTitleFromException(Throwable $e): string
    {
        return match (true) {
            $e instanceof QueryException => 'Loi du lieu he thong',
            $e instanceof \RuntimeException => 'Khong the hoan tat thao tac',
            $e instanceof \InvalidArgumentException => 'Du lieu gui len khong hop le',
            $e instanceof \TypeError => 'Du lieu he thong khong dung dinh dang',
            default => 'Khong the xu ly yeu cau',
        };
    }

    private function isSafeUserFacingMessage(string $message): bool
    {
        if ($message === '' || mb_strlen($message) > 220) {
            return false;
        }

        $unsafePatterns = [
            '/select\s.+from/i',
            '/insert\s+into/i',
            '/update\s+\w+/i',
            '/delete\s+from/i',
            '/at line \d+/i',
            '/[A-Z]:\\\\/i',
            '/\/var\/www\//i',
            '/vendor\//i',
            '/stack trace/i',
            '/syntax error/i',
            '/undefined (array key|variable|index|property)/i',
        ];

        foreach ($unsafePatterns as $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return false;
            }
        }

        return true;
    }
}
