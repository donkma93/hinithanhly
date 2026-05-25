<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user()) {
            return $response;
        }

        if ($request->expectsJson() || $request->is('sanctum/csrf-cookie')) {
            return $response;
        }

        if ($this->shouldSkipCrudMutation($request)) {
            return $response;
        }

        $route = $request->route();
        $payload = $request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
            'current_password',
            'image',
        ]);

        if ($request->hasFile('image')) {
            $payload['image'] = $request->file('image')->getClientOriginalName();
        }

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => $this->buildAction($request),
            'method' => $request->method(),
            'route_name' => $route?->getName(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $payload ?: null,
        ]);

        return $response;
    }

    private function buildAction(Request $request): string
    {
        $routeName = $request->route()?->getName();
        $method = $request->method();

        return trim(($routeName ?: $request->path()) . ' ' . $method);
    }

    private function shouldSkipCrudMutation(Request $request): bool
    {
        if (in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        $routeName = $request->route()?->getName();

        return is_string($routeName) && (
            str_starts_with($routeName, 'categories.')
            || str_starts_with($routeName, 'consignments.')
            || str_starts_with($routeName, 'products.')
            || str_starts_with($routeName, 'suppliers.')
        );
    }
}