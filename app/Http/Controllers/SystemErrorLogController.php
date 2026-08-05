<?php

namespace App\Http\Controllers;

use App\Models\SystemErrorLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemErrorLogController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = $this->resolvePerPage($request);

        $logs = SystemErrorLog::query()
            ->with('user:id,public_id,name,email')
            ->when($request->filled('error_uuid'), fn ($query) => $query->where('error_uuid', trim($request->string('error_uuid')->toString())))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('exception_class'), fn ($query) => $query->where('exception_class', trim($request->string('exception_class')->toString())))
            ->when($request->filled('status_code'), fn ($query) => $query->where('status_code', $request->integer('status_code')))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('system-logs.index', [
            'logs' => $logs,
            'users' => User::query()->orderBy('name')->get(['id', 'public_id', 'name']),
        ]);
    }
}
