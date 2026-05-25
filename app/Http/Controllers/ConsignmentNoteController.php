<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ConsignmentNote;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories\Contracts\ConsignmentNoteRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsignmentNoteController extends Controller
{
    public function __construct(private readonly ConsignmentNoteRepositoryInterface $consignments)
    {
        $this->middleware('permission:consignments.view')->only('index');
        $this->middleware('permission:consignments.create|consignments.manage')->only('store');
        $this->middleware('permission:consignments.update|consignments.manage')->only('update');
        $this->middleware('permission:consignments.delete|consignments.manage')->only('destroy');
    }

    public function index(Request $request): View
    {
        $publicId = trim($request->string('public_id')->toString());

        return view('consignments.index', [
            'consignments' => ConsignmentNote::query()
                ->select(['id', 'public_id', 'responsible_user_id', 'supplier_id', 'sent_date', 'quantity', 'created_at'])
                ->with([
                    'supplier:id,public_id,name',
                    'responsibleUser:id,public_id,name',
                ])
                ->when($publicId !== '', fn ($query) => $query->where('public_id', $publicId))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'public_id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'public_id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'responsible_user_id' => ['required', 'exists:users,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'sent_date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->consignments->create($data);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'consignments.store',
            'method' => $request->method(),
            'route_name' => 'consignments.store',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'supplier_id' => $data['supplier_id'],
                'responsible_user_id' => $data['responsible_user_id'],
                'sent_date' => $data['sent_date'],
                'quantity' => $data['quantity'],
            ],
        ]);

        return redirect()->route('consignments.index')->with('status', 'Đã tạo phiếu ký gửi.');
    }

    public function edit(ConsignmentNote $consignment): View
    {
        return view('consignments.edit', [
            'consignment' => $consignment,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, ConsignmentNote $consignment): RedirectResponse
    {
        $data = $request->validate([
            'responsible_user_id' => ['required', 'exists:users,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'sent_date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->consignments->update($consignment->id, $data);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'consignments.update',
            'method' => $request->method(),
            'route_name' => 'consignments.update',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'consignment_id' => $consignment->id,
                'supplier_id' => $data['supplier_id'],
                'responsible_user_id' => $data['responsible_user_id'],
                'sent_date' => $data['sent_date'],
                'quantity' => $data['quantity'],
            ],
        ]);

        return redirect()->route('consignments.index')->with('status', 'Đã cập nhật phiếu ký gửi.');
    }

    public function destroy(ConsignmentNote $consignment): RedirectResponse
    {
        $payload = [
            'consignment_id' => $consignment->id,
            'supplier_id' => $consignment->supplier_id,
            'responsible_user_id' => $consignment->responsible_user_id,
            'sent_date' => $consignment->sent_date,
            'quantity' => $consignment->quantity,
        ];

        $this->consignments->delete($consignment->id);

        AuditLog::record([
            'user_id' => request()->user()?->id,
            'action' => 'consignments.destroy',
            'method' => request()->method(),
            'route_name' => 'consignments.destroy',
            'path' => request()->path(),
            'status_code' => 302,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => $payload,
        ]);

        return redirect()->route('consignments.index')->with('status', 'Đã xoá phiếu ký gửi.');
    }
}