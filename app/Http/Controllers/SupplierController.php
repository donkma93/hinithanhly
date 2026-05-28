<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(private readonly SupplierRepositoryInterface $suppliers)
    {
        $this->middleware('permission:suppliers.view')->only('index');
        $this->middleware('permission:suppliers.create|suppliers.manage')->only('store');
        $this->middleware('permission:suppliers.update|suppliers.manage')->only('update');
        $this->middleware('permission:suppliers.delete')->only('destroy');
    }

    public function index(Request $request): View
    {
        $publicId = trim($request->string('public_id')->toString());
        $perPage = $this->resolvePerPage($request);

        return view('suppliers.index', [
            'suppliers' => Supplier::query()
                ->select(['id', 'public_id', 'responsible_name', 'type', 'name', 'phone', 'bank_name', 'created_at'])
                ->when($publicId !== '', fn ($query) => $query->where('public_id', $publicId))
                ->latest()
                ->paginate($perPage)
                ->withQueryString(),
            'bankOptions' => config('banks', []),
            'supplierTypes' => Supplier::TYPES,
            'supplierDiscountRates' => Setting::supplierDiscountRates(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Supplier::TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->suppliers->create($data);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'suppliers.store',
            'method' => $request->method(),
            'route_name' => 'suppliers.store',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'name' => $data['name'],
                'type' => $data['type'],
                'responsible_name' => $data['responsible_name'] ?? null,
            ],
        ]);

        return redirect()->route('suppliers.index')->with('status', 'Đã thêm nhà cung cấp.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', [
            'supplier' => $supplier,
            'bankOptions' => config('banks', []),
            'supplierTypes' => Supplier::TYPES,
            'supplierDiscountRates' => Setting::supplierDiscountRates(),
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Supplier::TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->suppliers->update($supplier->id, $data);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'suppliers.update',
            'method' => $request->method(),
            'route_name' => 'suppliers.update',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'supplier_id' => $supplier->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'responsible_name' => $data['responsible_name'] ?? null,
            ],
        ]);

        return redirect()->route('suppliers.index')->with('status', 'Đã cập nhật nhà cung cấp.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $payload = [
            'supplier_id' => $supplier->id,
            'name' => $supplier->name,
            'type' => $supplier->type,
        ];

        $this->suppliers->delete($supplier->id);

        AuditLog::record([
            'user_id' => request()->user()?->id,
            'action' => 'suppliers.destroy',
            'method' => request()->method(),
            'route_name' => 'suppliers.destroy',
            'path' => request()->path(),
            'status_code' => 302,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => $payload,
        ]);

        return redirect()->route('suppliers.index')->with('status', 'Đã xoá nhà cung cấp.');
    }
}