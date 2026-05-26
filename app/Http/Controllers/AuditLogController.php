<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = $this->resolvePerPage($request);

        $logs = AuditLog::query()
            ->with('user:id,public_id,name,email')
            ->when($request->filled('log_id'), fn ($query) => $query->whereKey($request->integer('log_id')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', 'like', '%'.$request->string('action')->toString().'%'))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('logs.index', [
            'logs' => $logs,
            'users' => User::query()->orderBy('name')->get(['id', 'public_id', 'name']),
        ]);
    }
}