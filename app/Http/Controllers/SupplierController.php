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
        $keyword = trim($request->string('public_id')->toString());
        $perPage = $this->resolvePerPage($request);

        $suppliersQuery = Supplier::query()
            ->select(['id', 'public_id', 'responsible_name', 'type', 'name', 'phone', 'bank_name', 'created_at']);

        if ($keyword !== '') {
            $suppliersQuery->where(function ($query) use ($keyword) {
                $query->where('public_id', $keyword)
                    ->orWhere('name', 'like', '%'.$keyword.'%')
                    ->orWhere('responsible_name', 'like', '%'.$keyword.'%');
            });
        }

        return view('suppliers.index', [
            'suppliers' => $suppliersQuery
                ->latest()
                ->paginate($perPage)
                ->withQueryString(),
            'bankOptions' => config('banks', []),
            'supplierTypes' => Supplier::ACTIVE_TYPES,
            'supplierDiscountRates' => Setting::supplierDiscountRates(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $requiresBankDetails = Supplier::requiresBankDetails($request->input('type'));

        $data = $request->validate([
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Supplier::ACTIVE_TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'bank_name' => [Rule::requiredIf($requiresBankDetails), 'nullable', 'string', 'max:255'],
            'bank_account_name' => [Rule::requiredIf($requiresBankDetails), 'nullable', 'string', 'max:255'],
            'bank_account_number' => [Rule::requiredIf($requiresBankDetails), 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! $requiresBankDetails) {
            $data['bank_name'] = null;
            $data['bank_account_name'] = null;
            $data['bank_account_number'] = null;
        }

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
        $supplierTypes = $supplier->type === 'ncc_nhieu_san_pham'
            ? ['ncc_nhieu_san_pham' => 'NCC nhiều sản phẩm (ngừng sử dụng)'] + Supplier::ACTIVE_TYPES
            : Supplier::ACTIVE_TYPES;

        return view('suppliers.edit', [
            'supplier' => $supplier,
            'bankOptions' => config('banks', []),
            'supplierTypes' => $supplierTypes,
            'supplierDiscountRates' => Setting::supplierDiscountRates(),
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $requiresBankDetails = Supplier::requiresBankDetails($request->input('type'));
        $allowedTypes = array_keys(Supplier::ACTIVE_TYPES);

        if ($supplier->type === 'ncc_nhieu_san_pham') {
            $allowedTypes[] = 'ncc_nhieu_san_pham';
        }

        $data = $request->validate([
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in($allowedTypes)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'bank_name' => [Rule::requiredIf($requiresBankDetails), 'nullable', 'string', 'max:255'],
            'bank_account_name' => [Rule::requiredIf($requiresBankDetails), 'nullable', 'string', 'max:255'],
            'bank_account_number' => [Rule::requiredIf($requiresBankDetails), 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! $requiresBankDetails) {
            $data['bank_name'] = null;
            $data['bank_account_name'] = null;
            $data['bank_account_number'] = null;
        }

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