<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupplierPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin|super-admin');
    }

    public function index(Request $request): View
    {
        $perPage = $this->resolvePerPage($request);
        $supplierId = $request->integer('supplier_id');
        $supplier = $supplierId ? Supplier::query()->find($supplierId) : Supplier::query()->orderBy('name')->first();
        $startDate = $request->filled('from') ? $request->date('from')->startOfDay() : now()->startOfMonth();
        $endDate = $request->filled('to') ? $request->date('to')->endOfDay() : now()->endOfDay();

        $summary = $supplier ? $this->buildSummary($supplier, $startDate, $endDate) : null;

        $payments = SupplierPayment::query()
            ->select(['id', 'public_id', 'supplier_id', 'user_id', 'payment_reference', 'period_from', 'period_to', 'gross_amount', 'discount_rate', 'discount_amount', 'payable_amount', 'bank_name', 'bank_account_name', 'bank_account_number', 'paid_at', 'created_at'])
            ->with(['supplier:id,public_id,name,type', 'handledBy:id,public_id,name'])
            ->when($supplier, fn ($query) => $query->where('supplier_id', $supplier->id))
            ->latest('paid_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('supplier-payments.index', [
            'suppliers' => Supplier::query()
                ->orderBy('name')
                ->get(['id', 'public_id', 'name', 'type', 'bank_name', 'bank_account_name', 'bank_account_number']),
            'selectedSupplier' => $supplier,
            'summary' => $summary,
            'payments' => $payments,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'supplierDiscountRates' => Setting::supplierDiscountRates(),
        ]);
    }

    public function createPayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $supplier = Supplier::query()->findOrFail($data['supplier_id']);
        $startDate = $request->date('from')->startOfDay();
        $endDate = $request->date('to')->endOfDay();
        $summary = $this->buildSummary($supplier, $startDate, $endDate);

        if ((float) $summary['gross_amount'] <= 0) {
            return response()->json(['message' => 'Không có doanh số nào để tạo thanh toán cho nhà cung cấp này.'], 422);
        }

        $bankCode = Setting::resolveBankCode((string) $supplier->bank_name);
        $bankName = Setting::resolveBankLabel($bankCode);
        $accountNumber = trim((string) $supplier->bank_account_number);
        $accountName = trim((string) $supplier->bank_account_name);

        if ($bankCode === '' || $accountNumber === '' || $accountName === '') {
            return response()->json(['message' => 'Nhà cung cấp chưa có đầy đủ thông tin ngân hàng.'], 422);
        }

        $paymentReference = Str::uuid()->toString();
        $paymentContent = sprintf('Thanh toan NCC %s', $supplier->public_id_display);
        $amount = (int) round((float) $summary['payable_amount']);

        $qrUrl = sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%s&addInfo=%s&accountName=%s',
            rawurlencode($bankCode),
            rawurlencode($accountNumber),
            rawurlencode((string) $amount),
            rawurlencode($paymentContent),
            rawurlencode($accountName)
        );

        $payload = "Nhà cung cấp: {$supplier->name}\nNgân hàng: {$bankName}\nSố tài khoản: {$accountNumber}\nChủ tài khoản: {$accountName}\nDoanh số gốc: " . number_format((float) $summary['gross_amount'], 0, ',', '.') . " ₫\nChiết khấu: {$summary['discount_rate']}% (-" . number_format((float) $summary['discount_amount'], 0, ',', '.') . " ₫)\nThanh toán: " . number_format((float) $summary['payable_amount'], 0, ',', '.') . " ₫\nNội dung: {$paymentContent}";

        $token = Str::random(24);

        Cache::put("supplier.payments.{$token}", [
            'supplier_id' => $supplier->id,
            'period_from' => $startDate->format('Y-m-d'),
            'period_to' => $endDate->format('Y-m-d'),
            'gross_amount' => (float) $summary['gross_amount'],
            'discount_rate' => (float) $summary['discount_rate'],
            'discount_amount' => (float) $summary['discount_amount'],
            'payable_amount' => (float) $summary['payable_amount'],
            'bank_name' => $bankName,
            'bank_account_name' => $accountName,
            'bank_account_number' => $accountNumber,
            'qr_url' => $qrUrl,
            'payload' => $payload,
            'reference' => $paymentReference,
        ], now()->addMinutes(30));

        return response()->json([
            'qr_url' => $qrUrl,
            'payload' => $payload,
            'payment_token' => $token,
            'supplier_name' => $supplier->name,
            'total' => (float) $summary['payable_amount'],
        ]);
    }

    public function confirmPayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payment_token' => ['required', 'string'],
        ]);

        $cached = Cache::pull("supplier.payments.{$data['payment_token']}");

        if (! $cached) {
            return response()->json(['message' => 'Mã thanh toán đã hết hạn hoặc không hợp lệ.'], 422);
        }

        $supplier = Supplier::query()->find($cached['supplier_id']);

        if (! $supplier) {
            return response()->json(['message' => 'Không tìm thấy nhà cung cấp.'], 422);
        }

        $payment = SupplierPayment::create([
            'supplier_id' => $supplier->id,
            'user_id' => $request->user()?->id,
            'payment_reference' => $cached['reference'],
            'period_from' => $cached['period_from'],
            'period_to' => $cached['period_to'],
            'gross_amount' => $cached['gross_amount'],
            'discount_rate' => $cached['discount_rate'],
            'discount_amount' => $cached['discount_amount'],
            'payable_amount' => $cached['payable_amount'],
            'bank_name' => $cached['bank_name'],
            'bank_account_name' => $cached['bank_account_name'],
            'bank_account_number' => $cached['bank_account_number'],
            'qr_url' => $cached['qr_url'],
            'payload' => $cached['payload'],
            'paid_at' => now(),
        ]);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'supplier-payments.confirm',
            'method' => $request->method(),
            'route_name' => 'supplier-payments.confirm',
            'path' => $request->path(),
            'status_code' => 200,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'supplier_payment_id' => $payment->id,
                'supplier_id' => $supplier->id,
                'payment_reference' => $payment->payment_reference,
                'payable_amount' => $payment->payable_amount,
            ],
        ]);

        return response()->json([
            'message' => 'Đã ghi nhận thanh toán nhà cung cấp.',
            'payment_id' => $payment->id,
        ]);
    }

    private function buildSummary(Supplier $supplier, $startDate, $endDate): array
    {
        $baseQuery = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('products.supplier_id', $supplier->id)
            ->whereNotNull('sales.completed_at')
            ->whereBetween('sales.completed_at', [$startDate, $endDate]);

        $grossAmount = (float) (clone $baseQuery)->sum('sale_items.line_total');
        $unitsSold = (int) (clone $baseQuery)->sum('sale_items.quantity');
        $lineItems = (int) (clone $baseQuery)->count();
        $discountRate = (float) Setting::supplierDiscountRate($supplier->type);
        $discountAmount = round($grossAmount * $discountRate / 100, 2);
        $payableAmount = max(0, round($grossAmount - $discountAmount, 2));

        return [
            'gross_amount' => $grossAmount,
            'discount_rate' => $discountRate,
            'discount_amount' => $discountAmount,
            'payable_amount' => $payableAmount,
            'units_sold' => $unitsSold,
            'line_items' => $lineItems,
        ];
    }
}