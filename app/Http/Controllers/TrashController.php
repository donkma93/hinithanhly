<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class TrashController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin|super-admin');
    }

    public function index(): View
    {
        $sections = [
            $this->buildCategorySection(),
            $this->buildSupplierSection(),
            $this->buildConsignmentSection(),
            $this->buildProductSection(),
            $this->buildUserSection(),
            $this->buildPermissionSection(),
        ];

        return view('trash.index', [
            'sections' => $sections,
            'totalCount' => array_sum(array_map(fn (array $section): int => $section['count'], $sections)),
        ]);
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        $record = $this->findTrashedRecord($type, $id);
        $reason = $this->restoreBlockReason($type, $record);

        if ($reason !== null) {
            return redirect()->route('trash.index')->with('error', $reason);
        }

        $record->restore();
        $this->refreshPermissionCache($type);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'trash.restore',
            'method' => $request->method(),
            'route_name' => 'trash.restore',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $this->buildAuditPayload($type, $record),
        ]);

        return redirect()->route('trash.index')->with('status', $this->sectionLabel($type).' đã được khôi phục.');
    }

    public function forceDestroy(Request $request, string $type, int $id): RedirectResponse
    {
        $record = $this->findTrashedRecord($type, $id);
        $reason = $this->forceBlockReason($type, $record);

        if ($reason !== null) {
            return redirect()->route('trash.index')->with('warning', $reason);
        }

        $payload = $this->buildAuditPayload($type, $record);
        $label = $this->recordLabel($type, $record);

        $this->forceDeleteRecord($type, $record);
        $this->refreshPermissionCache($type);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'trash.force_destroy',
            'method' => $request->method(),
            'route_name' => 'trash.destroy',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $payload,
        ]);

        return redirect()->route('trash.index')->with('status', $this->sectionLabel($type).' đã được xóa vĩnh viễn: '.$label.'.');
    }

    public function empty(Request $request, string $type): RedirectResponse
    {
        $items = $this->queryTrashedRecords($type)->get();
        [$deletedCount, $skippedItems] = $this->purgeItems($request, $type, $items);

        return redirect()->route('trash.index')->with(
            $skippedItems !== [] ? 'warning' : 'status',
            $this->buildPurgeMessage($type, $deletedCount, $skippedItems)
        );
    }

    public function emptyAll(Request $request): RedirectResponse
    {
        $deletedCount = 0;
        $skippedItems = [];

        foreach ($this->purgeOrder() as $type) {
            $items = $this->queryTrashedRecords($type)->get();
            [$typeDeletedCount, $typeSkippedItems] = $this->purgeItems($request, $type, $items);

            $deletedCount += $typeDeletedCount;

            foreach ($typeSkippedItems as $itemLabel) {
                $skippedItems[] = $this->sectionLabel($type).': '.$itemLabel;
            }
        }

        return redirect()->route('trash.index')->with(
            $skippedItems !== [] ? 'warning' : 'status',
            $this->buildPurgeAllMessage($deletedCount, $skippedItems)
        );
    }

    private function buildCategorySection(): array
    {
        $items = Category::query()
            ->onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get(['id', 'public_id', 'name', 'deleted_at'])
            ->map(function (Category $category): array {
                $forceReason = $this->forceBlockReason('categories', $category);

                return [
                    'id' => $category->id,
                    'code' => '#'.$category->public_id_display,
                    'title' => $category->name,
                    'subtitle' => 'Mã công khai #'.$category->public_id_display,
                    'meta' => [
                        'Đã xóa lúc: '.$category->deleted_at?->format('d/m/Y H:i'),
                    ],
                    'restore_url' => route('trash.restore', ['type' => 'categories', 'id' => $category->id]),
                    'force_url' => route('trash.destroy', ['type' => 'categories', 'id' => $category->id]),
                    'forceable' => $forceReason === null,
                    'force_reason' => $forceReason,
                ];
            })
            ->values()
            ->all();

        return [
            'key' => 'categories',
            'label' => 'Danh mục',
            'description' => 'Danh mục đã bị đưa vào thùng rác.',
            'count' => count($items),
            'items' => $items,
        ];
    }

    private function buildSupplierSection(): array
    {
        $items = Supplier::query()
            ->onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get(['id', 'public_id', 'type', 'name', 'deleted_at'])
            ->map(function (Supplier $supplier): array {
                $forceReason = $this->forceBlockReason('suppliers', $supplier);

                return [
                    'id' => $supplier->id,
                    'code' => '#'.$supplier->public_id_display,
                    'title' => $supplier->name,
                    'subtitle' => Supplier::labelForType($supplier->type),
                    'meta' => [
                        'Loại: '.Supplier::labelForType($supplier->type),
                        'Đã xóa lúc: '.$supplier->deleted_at?->format('d/m/Y H:i'),
                    ],
                    'restore_url' => route('trash.restore', ['type' => 'suppliers', 'id' => $supplier->id]),
                    'force_url' => route('trash.destroy', ['type' => 'suppliers', 'id' => $supplier->id]),
                    'forceable' => $forceReason === null,
                    'force_reason' => $forceReason,
                ];
            })
            ->values()
            ->all();

        return [
            'key' => 'suppliers',
            'label' => 'Nhà cung cấp',
            'description' => 'Nhà cung cấp đã bị đưa vào thùng rác.',
            'count' => count($items),
            'items' => $items,
        ];
    }

    private function buildConsignmentSection(): array
    {
        $items = ConsignmentNote::query()
            ->onlyTrashed()
            ->with(['supplier:id,public_id,name'])
            ->orderByDesc('deleted_at')
            ->get(['id', 'public_id', 'responsible_name', 'supplier_id', 'sent_date', 'quantity', 'deleted_at'])
            ->map(function (ConsignmentNote $consignment): array {
                $forceReason = $this->forceBlockReason('consignments', $consignment);

                return [
                    'id' => $consignment->id,
                    'code' => '#'.$consignment->public_id_display,
                    'title' => $consignment->responsible_name,
                    'subtitle' => $consignment->supplier?->name ?? '---',
                    'meta' => [
                        'Nhà cung cấp: '.($consignment->supplier?->public_id ? '#'.$consignment->supplier->public_id_display.' - '.$consignment->supplier->name : '---'),
                        'Ngày gửi: '.optional($consignment->sent_date)->format('d/m/Y'),
                        'Số lượng: '.$consignment->quantity,
                        'Đã xóa lúc: '.$consignment->deleted_at?->format('d/m/Y H:i'),
                    ],
                    'restore_url' => route('trash.restore', ['type' => 'consignments', 'id' => $consignment->id]),
                    'force_url' => route('trash.destroy', ['type' => 'consignments', 'id' => $consignment->id]),
                    'forceable' => $forceReason === null,
                    'force_reason' => $forceReason,
                ];
            })
            ->values()
            ->all();

        return [
            'key' => 'consignments',
            'label' => 'Phiếu ký gửi',
            'description' => 'Phiếu ký gửi đã bị đưa vào thùng rác.',
            'count' => count($items),
            'items' => $items,
        ];
    }

    private function buildProductSection(): array
    {
        $items = Product::query()
            ->onlyTrashed()
            ->with([
                'supplier:id,public_id,name',
                'category:id,public_id,name',
                'consignmentNote:id,public_id,supplier_id,sent_date',
            ])
            ->orderByDesc('deleted_at')
            ->get(['id', 'public_id', 'supplier_id', 'category_id', 'consignment_note_id', 'name', 'sale_price', 'quantity', 'image_path', 'deleted_at'])
            ->map(function (Product $product): array {
                return [
                    'id' => $product->id,
                    'code' => '#'.$product->public_id_display,
                    'title' => $product->name,
                    'subtitle' => $product->supplier?->name ?? '---',
                    'meta' => [
                        'Nhà cung cấp: '.($product->supplier?->public_id ? '#'.$product->supplier->public_id_display.' - '.$product->supplier->name : '---'),
                        'Danh mục: '.($product->category?->public_id ? '#'.$product->category->public_id_display.' - '.$product->category->name : '---'),
                        'Giá bán: '.number_format($product->sale_price ?? 0, 0, ',', '.').' đ',
                        'Số lượng: '.$product->quantity,
                        'Đã xóa lúc: '.$product->deleted_at?->format('d/m/Y H:i'),
                    ],
                    'thumbnail_url' => $product->image_path ? asset('storage/'.$product->image_path) : null,
                    'thumbnail_alt' => $product->name,
                    'restore_url' => route('trash.restore', ['type' => 'products', 'id' => $product->id]),
                    'force_url' => route('trash.destroy', ['type' => 'products', 'id' => $product->id]),
                    'forceable' => true,
                    'force_reason' => null,
                ];
            })
            ->values()
            ->all();

        return [
            'key' => 'products',
            'label' => 'Sản phẩm',
            'description' => 'Sản phẩm đã bị đưa vào thùng rác.',
            'count' => count($items),
            'items' => $items,
        ];
    }

    private function buildUserSection(): array
    {
        $items = User::query()
            ->onlyTrashed()
            ->with(['roles:id,name'])
            ->orderByDesc('deleted_at')
            ->get(['id', 'public_id', 'name', 'email', 'deleted_at'])
            ->map(function (User $user): array {
                $forceReason = $this->forceBlockReason('users', $user);

                return [
                    'id' => $user->id,
                    'code' => '#'.$user->public_id_display,
                    'title' => $user->name,
                    'subtitle' => $user->email,
                    'meta' => [
                        'Vai trò: '.($user->roles->pluck('name')->implode(', ') ?: '---'),
                        'Đã xóa lúc: '.$user->deleted_at?->format('d/m/Y H:i'),
                    ],
                    'restore_url' => route('trash.restore', ['type' => 'users', 'id' => $user->id]),
                    'force_url' => route('trash.destroy', ['type' => 'users', 'id' => $user->id]),
                    'forceable' => $forceReason === null,
                    'force_reason' => $forceReason,
                ];
            })
            ->values()
            ->all();

        return [
            'key' => 'users',
            'label' => 'Tài khoản',
            'description' => 'Tài khoản đã bị đưa vào thùng rác.',
            'count' => count($items),
            'items' => $items,
        ];
    }

    private function buildPermissionSection(): array
    {
        $items = Permission::query()
            ->onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get(['id', 'name', 'guard_name', 'deleted_at'])
            ->map(function (Permission $permission): array {
                return [
                    'id' => $permission->id,
                    'code' => $permission->name,
                    'title' => $permission->name,
                    'subtitle' => $permission->guard_name,
                    'meta' => [
                        'Guard: '.$permission->guard_name,
                        'Đã xóa lúc: '.$permission->deleted_at?->format('d/m/Y H:i'),
                    ],
                    'restore_url' => route('trash.restore', ['type' => 'permissions', 'id' => $permission->id]),
                    'force_url' => route('trash.destroy', ['type' => 'permissions', 'id' => $permission->id]),
                    'forceable' => true,
                    'force_reason' => null,
                ];
            })
            ->values()
            ->all();

        return [
            'key' => 'permissions',
            'label' => 'Quyền',
            'description' => 'Quyền đã bị đưa vào thùng rác.',
            'count' => count($items),
            'items' => $items,
        ];
    }

    private function queryTrashedRecords(string $type)
    {
        return match ($type) {
            'categories' => Category::query()->onlyTrashed()->orderByDesc('deleted_at'),
            'suppliers' => Supplier::query()->onlyTrashed()->orderByDesc('deleted_at'),
            'consignments' => ConsignmentNote::query()->onlyTrashed()->orderByDesc('deleted_at'),
            'products' => Product::query()->onlyTrashed()->orderByDesc('deleted_at'),
            'users' => User::query()->onlyTrashed()->orderByDesc('deleted_at'),
            'permissions' => Permission::query()->onlyTrashed()->orderByDesc('deleted_at'),
            default => abort(404),
        };
    }

    private function findTrashedRecord(string $type, int $id): object
    {
        return $this->queryTrashedRecords($type)->whereKey($id)->firstOrFail();
    }

    private function restoreBlockReason(string $type, object $record): ?string
    {
        return match ($type) {
            'products' => $this->productRestoreReason($record),
            'consignments' => $this->consignmentRestoreReason($record),
            default => null,
        };
    }

    private function forceBlockReason(string $type, object $record): ?string
    {
        return match ($type) {
            'categories' => Product::query()->withTrashed()->where('category_id', $record->id)->exists()
                ? 'Danh mục này vẫn còn sản phẩm liên quan nên chưa thể xóa vĩnh viễn.'
                : null,
            'suppliers' => $this->supplierForceBlockReason($record),
            'consignments' => Product::query()->withTrashed()->where('consignment_note_id', $record->id)->exists()
                ? 'Phiếu ký gửi này vẫn còn sản phẩm liên quan nên chưa thể xóa vĩnh viễn.'
                : null,
            'users' => ConsignmentNote::query()->withTrashed()->where('responsible_user_id', $record->id)->exists()
                ? 'Tài khoản này vẫn còn phiếu ký gửi liên quan nên chưa thể xóa vĩnh viễn.'
                : null,
            default => null,
        };
    }

    private function supplierForceBlockReason(object $record): ?string
    {
        if (Product::query()->withTrashed()->where('supplier_id', $record->id)->exists()) {
            return 'Nhà cung cấp này vẫn còn sản phẩm liên quan nên chưa thể xóa vĩnh viễn.';
        }

        if (ConsignmentNote::query()->withTrashed()->where('supplier_id', $record->id)->exists()) {
            return 'Nhà cung cấp này vẫn còn phiếu ký gửi liên quan nên chưa thể xóa vĩnh viễn.';
        }

        if (SupplierPayment::query()->where('supplier_id', $record->id)->exists()) {
            return 'Nhà cung cấp này vẫn còn lịch sử thanh toán nên chưa thể xóa vĩnh viễn.';
        }

        return null;
    }

    private function productRestoreReason(object $record): ?string
    {
        if (! Category::query()->withTrashed()->whereKey($record->category_id)->exists()) {
            return 'Không thể khôi phục sản phẩm vì danh mục gốc đã không còn tồn tại.';
        }

        if (! Supplier::query()->withTrashed()->whereKey($record->supplier_id)->exists()) {
            return 'Không thể khôi phục sản phẩm vì nhà cung cấp gốc đã không còn tồn tại.';
        }

        if (! ConsignmentNote::query()->withTrashed()->whereKey($record->consignment_note_id)->exists()) {
            return 'Không thể khôi phục sản phẩm vì phiếu ký gửi gốc đã không còn tồn tại.';
        }

        return null;
    }

    private function consignmentRestoreReason(object $record): ?string
    {
        if (! Supplier::query()->withTrashed()->whereKey($record->supplier_id)->exists()) {
            return 'Không thể khôi phục phiếu ký gửi vì nhà cung cấp gốc đã không còn tồn tại.';
        }

        return null;
    }

    private function forceDeleteRecord(string $type, object $record): void
    {
        if ($type === 'products' && $record->image_path) {
            $imagePath = $record->image_path;
            $record->forceDelete();
            Storage::disk('public')->delete($imagePath);

            return;
        }

        $record->forceDelete();
    }

    private function refreshPermissionCache(string $type): void
    {
        if ($type === 'permissions') {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    /**
     * @param  iterable<object>  $records
     * @return array{0:int,1:array<int, string>}
     */
    private function purgeItems(Request $request, string $type, iterable $records): array
    {
        $deletedCount = 0;
        $skippedItems = [];

        foreach ($records as $record) {
            $reason = $this->forceBlockReason($type, $record);

            if ($reason !== null) {
                $skippedItems[] = $this->recordLabel($type, $record).' - '.$reason;

                continue;
            }

            $payload = $this->buildAuditPayload($type, $record);
            $label = $this->recordLabel($type, $record);

            $this->forceDeleteRecord($type, $record);
            $this->refreshPermissionCache($type);

            AuditLog::record([
                'user_id' => $request->user()?->id,
                'action' => 'trash.force_destroy',
                'method' => $request->method(),
                'route_name' => 'trash.destroy',
                'path' => $request->path(),
                'status_code' => 302,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => $payload + ['label' => $label],
            ]);

            $deletedCount++;
        }

        return [$deletedCount, $skippedItems];
    }

    private function buildPurgeMessage(string $type, int $deletedCount, array $skippedItems): string
    {
        $label = $this->sectionLabel($type);

        if ($deletedCount === 0 && $skippedItems === []) {
            return $label.' hiện không có mục nào trong thùng rác.';
        }

        if ($skippedItems !== []) {
            return $label.' đã dọn sạch '.$deletedCount.' mục, nhưng vẫn còn một số mục chưa thể xóa vĩnh viễn: '.implode('; ', array_slice($skippedItems, 0, 3)).(count($skippedItems) > 3 ? '...' : '');
        }

        return $label.' đã được dọn sạch '.$deletedCount.' mục trong thùng rác.';
    }

    private function buildPurgeAllMessage(int $deletedCount, array $skippedItems): string
    {
        if ($deletedCount === 0 && $skippedItems === []) {
            return 'Thùng rác hiện đang trống.';
        }

        if ($skippedItems !== []) {
            return 'Đã dọn sạch '.$deletedCount.' mục trong thùng rác, nhưng vẫn còn một số mục chưa thể xóa vĩnh viễn: '.implode('; ', array_slice($skippedItems, 0, 4)).(count($skippedItems) > 4 ? '...' : '');
        }

        return 'Đã dọn sạch hoàn toàn thùng rác với '.$deletedCount.' mục.';
    }

    private function sectionLabel(string $type): string
    {
        return match ($type) {
            'categories' => 'Danh mục',
            'suppliers' => 'Nhà cung cấp',
            'consignments' => 'Phiếu ký gửi',
            'products' => 'Sản phẩm',
            'users' => 'Tài khoản',
            'permissions' => 'Quyền',
            default => abort(404),
        };
    }

    private function recordLabel(string $type, object $record): string
    {
        return match ($type) {
            'categories' => '#'.$record->public_id_display.' - '.$record->name,
            'suppliers' => '#'.$record->public_id_display.' - '.$record->name,
            'consignments' => '#'.$record->public_id_display.' - '.$record->responsible_name,
            'products' => '#'.$record->public_id_display.' - '.$record->name,
            'users' => '#'.$record->public_id_display.' - '.$record->name,
            'permissions' => $record->name,
            default => abort(404),
        };
    }

    private function buildAuditPayload(string $type, object $record): array
    {
        return match ($type) {
            'categories' => [
                'type' => $type,
                'id' => $record->id,
                'name' => $record->name,
            ],
            'suppliers' => [
                'type' => $type,
                'id' => $record->id,
                'name' => $record->name,
                'supplier_type' => $record->type,
            ],
            'consignments' => [
                'type' => $type,
                'id' => $record->id,
                'responsible_name' => $record->responsible_name,
                'supplier_id' => $record->supplier_id,
                'sent_date' => optional($record->sent_date)->toDateString(),
                'quantity' => $record->quantity,
            ],
            'products' => [
                'type' => $type,
                'id' => $record->id,
                'name' => $record->name,
                'supplier_id' => $record->supplier_id,
                'category_id' => $record->category_id,
                'consignment_note_id' => $record->consignment_note_id,
                'quantity' => $record->quantity,
            ],
            'users' => [
                'type' => $type,
                'id' => $record->id,
                'name' => $record->name,
                'email' => $record->email,
            ],
            'permissions' => [
                'type' => $type,
                'id' => $record->id,
                'name' => $record->name,
                'guard_name' => $record->guard_name,
            ],
            default => abort(404),
        };
    }

    /**
     * @return array<int, string>
     */
    private function purgeOrder(): array
    {
        return ['products', 'consignments', 'categories', 'suppliers', 'users', 'permissions'];
    }
}
