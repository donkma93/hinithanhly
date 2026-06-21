<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        $selectedMonth = trim((string) $request->string('month'));
        $monthDate = $selectedMonth !== ''
            ? Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth()
            : now()->startOfMonth();
        $selectedMonth = $monthDate->format('Y-m');
        $supplierId = $request->integer('supplier_id');
        $status = trim((string) $request->string('status', ''));
        $startDate = $monthDate->copy()->startOfMonth()->startOfDay();
        $endDate = $monthDate->copy()->endOfMonth()->endOfDay();
        $hasSupplierFilter = $supplierId > 0;
        $supplier = $hasSupplierFilter
            ? Supplier::query()->withTrashed()->find($supplierId)
            : null;

        // Aggregate sales by supplier for the selected period
        $aggregates = SaleItem::query()
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where(function ($query) use ($startDate, $endDate): void {
                $query->whereBetween(DB::raw('COALESCE(sales.completed_at, sales.created_at)'), [$startDate, $endDate]);
            })
            ->groupBy('products.supplier_id')
            ->selectRaw('products.supplier_id as supplier_id, SUM(sale_items.line_total) as gross_amount, SUM(sale_items.quantity) as units_sold, COUNT(*) as line_items')
            ->get()
            ->keyBy('supplier_id');

        $paymentsForMonth = SupplierPayment::query()
            ->with(['supplier:id,public_id,name,type,phone', 'handledBy:id,public_id,name'])
            ->whereDate('period_from', '>=', $startDate->toDateString())
            ->whereDate('period_to', '<=', $endDate->toDateString())
            ->whereNotNull('paid_at')
            ->get()
            ->keyBy('supplier_id');

        $supplierDiscountRates = Setting::supplierDiscountRates();

        $supplierRows = Supplier::query()->orderBy('name')
            ->get(['id', 'public_id', 'responsible_name', 'name', 'phone', 'type', 'bank_name', 'bank_account_name', 'bank_account_number'])
            ->map(function (Supplier $s) use ($aggregates, $supplierDiscountRates, $startDate, $endDate) {
                $agg = $aggregates->get($s->id);
                $gross = $agg ? (float) $agg->gross_amount : 0.0;
                $units = $agg ? (int) $agg->units_sold : 0;
                $discountRate = isset($supplierDiscountRates[$s->type]) ? (float) $supplierDiscountRates[$s->type] : 0.0;
                $discountAmount = round($gross * $discountRate / 100, 2);
                $payable = max(0, round($gross - $discountAmount, 2));

                return [
                    'supplier' => $s,
                    'period_label' => $startDate->format('Y-m'),
                    'gross_amount' => $gross,
                    'units_sold' => $units,
                    'discount_rate' => $discountRate,
                    'discount_amount' => $discountAmount,
                    'payable_amount' => $payable,
                ];
            })
            ->map(function (array $row) use ($paymentsForMonth) {
                $payment = $paymentsForMonth->get($row['supplier']->id);
                $isPaid = $payment !== null;

                return [
                    ...$row,
                    'status' => $isPaid ? 'paid' : 'unpaid',
                    'status_label' => $isPaid ? 'Đã thanh toán' : 'Chưa thanh toán',
                    'paid_amount' => $isPaid ? (float) $payment->payable_amount : 0.0,
                    'outstanding_amount' => $isPaid ? 0.0 : (float) $row['payable_amount'],
                    'payment' => $payment,
                ];
            })
            ->filter(function (array $row) use ($supplierId, $status) {
                if ($supplierId > 0 && (int) $row['supplier']->id !== $supplierId) {
                    return false;
                }

                if ($status !== '' && $row['status'] !== $status) {
                    return false;
                }

                return (float) $row['payable_amount'] > 0 || $row['payment'] !== null;
            })
            ->values();

        $overview = [
            'period' => $selectedMonth,
            'paid_amount' => round((float) $supplierRows->sum('paid_amount'), 2),
            'unpaid_amount' => round((float) $supplierRows->sum('outstanding_amount'), 2),
        ];
        $overview['total_amount'] = round($overview['paid_amount'] + $overview['unpaid_amount'], 2);

        $selectedRow = $supplier
            ? $supplierRows->firstWhere('supplier.id', $supplier->id)
            : null;

        $payments = SupplierPayment::query()
            ->select(['id', 'public_id', 'supplier_id', 'user_id', 'payment_reference', 'period_from', 'period_to', 'gross_amount', 'discount_rate', 'discount_amount', 'payable_amount', 'bank_name', 'bank_account_name', 'bank_account_number', 'paid_at', 'created_at'])
            ->with(['supplier:id,public_id,name,type', 'handledBy:id,public_id,name'])
            ->whereDate('period_from', '>=', $startDate->toDateString())
            ->whereDate('period_to', '<=', $endDate->toDateString())
            ->when($supplier, fn ($query) => $query->where('supplier_id', $supplier->id))
            ->latest('paid_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('supplier-payments.index', [
            'suppliers' => Supplier::query()
                ->orderBy('name')
                ->get(['id', 'public_id', 'name', 'phone', 'type', 'bank_name', 'bank_account_name', 'bank_account_number']),
            'selectedSupplier' => $supplier,
            'selectedSupplierRow' => $selectedRow,
            'supplierRows' => $supplierRows,
            'overview' => $overview,
            'selectedMonth' => $selectedMonth,
            'status' => $status,
            'hasSupplierFilter' => $hasSupplierFilter,
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

        $supplier = Supplier::query()->withTrashed()->findOrFail($data['supplier_id']);
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
        $paymentContent = sprintf('Hinikygui gui ncc %s doanh thu T%s', $supplier->name, $startDate->format('n'));
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

        $supplier = Supplier::query()->withTrashed()->find($cached['supplier_id']);

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
            ->whereBetween(DB::raw('COALESCE(sales.completed_at, sales.created_at)'), [$startDate, $endDate]);

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
