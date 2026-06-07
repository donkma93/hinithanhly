<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Product;
use App\Models\Supplier;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Picqer\Barcode\BarcodeGeneratorSVG;

class ProductController extends Controller
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
        $this->middleware('permission:products.view')->only(['index', 'expiryIndex', 'labelIndex', 'printLabels', 'label', 'barcode']);
        $this->middleware('permission:products.create|products.manage')->only('store');
        $this->middleware('permission:products.update|products.manage')->only(['update', 'returnToSupplier']);
        $this->middleware('permission:products.delete')->only('destroy');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', $request->input('public_id', '')));
        $search = ltrim($search, '#');
        $perPage = $this->resolvePerPage($request);
        $filterSupplierId = $request->input('supplier_id') ? (int) $request->input('supplier_id') : null;
        $products = Product::query()
            ->select(['id', 'public_id', 'consignment_note_id', 'supplier_id', 'category_id', 'created_by_id', 'name', 'sale_price', 'quantity', 'image_path', 'description', 'returned_at', 'returned_by_id', 'created_at'])
            ->with([
                'category:id,public_id,name',
                'supplier:id,public_id,name',
                'consignmentNote:id,public_id,supplier_id,responsible_user_id,sent_date',
                'consignmentNote.responsibleUser:id,public_id,name',
                'returner:id,public_id,name',
            ])
            ->when($filterSupplierId !== null, function ($query) use ($filterSupplierId): void {
                $query->where('supplier_id', $filterSupplierId);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('public_id', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search): void {
                            $supplierQuery->where('public_id', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $sendSummaryMap = $this->resolveSendSummaries(
            $products->getCollection()->pluck('supplier_id')->unique()->all()
        );

        $products->setCollection(
            $products->getCollection()->map(function (Product $product) use ($sendSummaryMap): Product {
                $sendSummary = $sendSummaryMap[$product->consignment_note_id] ?? [
                    'round' => 1,
                    'days' => 0,
                    'label' => 'Lần 1 / 0 ngày / ---',
                ];

                $product->setAttribute('send_round', $sendSummary['round']);
                $product->setAttribute('send_days', $sendSummary['days']);
                $product->setAttribute('send_summary', $sendSummary['label']);

                return $product;
            })
        );

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'public_id', 'name', 'type']);

        $consignmentExpirySummary = $this->resolveConsignmentExpirySummary();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(['id', 'public_id', 'name']),
            'suppliers' => $suppliers,
            'filterSupplierId' => $filterSupplierId,
            'filterSupplierOptions' => $suppliers->map(fn ($s) => [
                'value' => $s->id,
                'label' => '#'.$s->public_id_display.' - '.$s->name,
            ])->all(),
            'consignmentExpirySummary' => $consignmentExpirySummary,
            'consignmentOptions' => $this->buildConsignmentOptions(
                ConsignmentNote::query()
                    ->whereHas('supplier', fn ($query) => $query->whereIn('type', Supplier::MANUAL_CONSIGNMENT_TYPES))
                    ->orderByDesc('sent_date')
                    ->orderByDesc('id')
                    ->get(['id', 'public_id', 'supplier_id', 'sent_date'])
            ),
        ]);
    }

    public function expiryIndex(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $search = ltrim($search, '#');
        $perPage = $this->resolvePerPage($request);
        $supplierId = $request->input('supplier_id') ? (int) $request->input('supplier_id') : null;
        $status = $request->string('status', 'pending')->toString();
        $allowedStatuses = ['pending', 'expiring_soon', 'expired', 'returned'];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        [$warningWindowStart, $warningWindowEnd] = $this->consignmentExpiryWindows();

        $products = Product::query()
            ->select([
                'products.id',
                'products.public_id',
                'products.consignment_note_id',
                'products.supplier_id',
                'products.category_id',
                'products.created_by_id',
                'products.name',
                'products.sale_price',
                'products.quantity',
                'products.image_path',
                'products.description',
                'products.returned_at',
                'products.returned_by_id',
                'products.created_at',
            ])
            ->leftJoin('consignment_notes', 'consignment_notes.id', '=', 'products.consignment_note_id')
            ->with([
                'category:id,public_id,name',
                'supplier:id,public_id,name',
                'consignmentNote:id,public_id,supplier_id,responsible_user_id,sent_date',
                'consignmentNote.responsibleUser:id,public_id,name',
                'returner:id,public_id,name',
            ])
            ->when($supplierId !== null, function (Builder $query) use ($supplierId): void {
                $query->where('products.supplier_id', $supplierId);
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('products.public_id', 'like', '%'.$search.'%')
                        ->orWhere('products.name', 'like', '%'.$search.'%')
                        ->orWhereHas('supplier', function (Builder $supplierQuery) use ($search): void {
                            $supplierQuery->where('public_id', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%');
                        });
                });
            });

        switch ($status) {
            case 'returned':
                $products->whereNotNull('products.returned_at');
                break;

            case 'expiring_soon':
                $products->whereNull('products.returned_at')
                    ->where('products.quantity', '>', 0)
                    ->whereBetween('consignment_notes.sent_date', [$warningWindowStart, $warningWindowEnd]);
                break;

            case 'expired':
                $products->whereNull('products.returned_at')
                    ->where('products.quantity', '>', 0)
                    ->whereDate('consignment_notes.sent_date', '<', $warningWindowStart);
                break;

            default:
                $products->whereNull('products.returned_at')
                    ->where('products.quantity', '>', 0)
                    ->whereDate('consignment_notes.sent_date', '<=', $warningWindowEnd);
                break;
        }

        $products = $products
            ->orderByRaw('CASE WHEN products.returned_at IS NOT NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN consignment_notes.sent_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('consignment_notes.sent_date')
            ->orderByDesc('products.created_at')
            ->paginate($perPage)
            ->withQueryString();

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'public_id', 'name', 'type']);

        return view('products.expiry', [
            'products' => $products,
            'suppliers' => $suppliers,
            'filterSupplierId' => $supplierId,
            'filterSupplierOptions' => $suppliers->map(fn ($supplier) => [
                'value' => $supplier->id,
                'label' => '#'.$supplier->public_id_display.' - '.$supplier->name,
            ])->all(),
            'filterStatus' => $status,
            'filterSearch' => $search,
            'expiryStatusOptions' => [
                'pending' => [
                    'label' => 'Cần xử lý',
                    'description' => 'Sắp hết hạn hoặc đã quá hạn',
                ],
                'expiring_soon' => [
                    'label' => 'Sắp hết hạn',
                    'description' => 'Còn tối đa 7 ngày',
                ],
                'expired' => [
                    'label' => 'Quá hạn',
                    'description' => 'Đã qua mốc 45 ngày',
                ],
                'returned' => [
                    'label' => 'Đã trả',
                    'description' => 'Đã đánh dấu trả cho người gửi',
                ],
            ],
            'consignmentExpirySummary' => $this->resolveConsignmentExpirySummary($supplierId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($response = $this->preflightImageUpload($request, 'store')) {
            return $response;
        }

        // Pre-check uploaded file for PHP upload errors to log helpful diagnostics
        if ($file = $this->resolveUploadedImage($request)) {
            $error = $file->getError();
            if ($error !== UPLOAD_ERR_OK) {
                Log::error('Product image upload error (store)', [
                    'error_code' => $error,
                    'file_info' => is_object($file) ? [
                        'clientName' => $file->getClientOriginalName(),
                        'clientMime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ] : null,
                    'files' => $_FILES,
                ]);

                return redirect()->back()->withInput()->withErrors(['image' => 'File upload error (code '.$error.'). Vui lòng thử lại.']);
            }
        } else {
            // If client attempted upload but no file present, log raw FILES for diagnostics
            if ($this->requestHasUnexpectedImageFileEntries()) {
                Log::warning('No image file detected in request, but $_FILES is not empty (store)', ['files' => $_FILES]);
            }
        }

        $validated = $this->validatedData($request);
        $data = $validated['data'];
        $supplier = $validated['supplier'];
        $data['created_by_id'] = $request->user()?->id;

        $image = $this->resolveUploadedImage($request);

        if ($image instanceof UploadedFile) {
            $data['image_path'] = $this->storeOptimizedImage($image);
        }

        $result = DB::transaction(function () use ($data, $request, $supplier): array {
            $consignmentNote = $this->resolveConsignmentNoteForProduct($supplier, $request, $data);
            $data['consignment_note_id'] = $consignmentNote->id;

            return [
                'product' => $this->products->create($data),
                'consignmentNote' => $consignmentNote,
            ];
        });

        /** @var \App\Models\ConsignmentNote $consignmentNote */
        $consignmentNote = $result['consignmentNote'];

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'products.store',
            'method' => $request->method(),
            'route_name' => 'products.store',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'supplier_id' => $data['supplier_id'],
                'consignment_note_id' => $consignmentNote->id,
                'quantity' => $data['quantity'],
            ],
        ]);

        $status = 'Đã thêm sản phẩm.';

        if ($supplier->usesAutoGeneratedConsignment()) {
            $status .= ' Phiếu ký gửi tự sinh #'.$consignmentNote->public_id.'.';
        }

        return redirect()->route('products.index')->with('status', $status);
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => Category::query()->withTrashed()->orderBy('name')->get(['id', 'public_id', 'name']),
            'suppliers' => Supplier::query()->withTrashed()->orderBy('name')->get(['id', 'public_id', 'name', 'type']),
            'consignmentOptions' => $this->buildConsignmentOptions(
                ConsignmentNote::query()->withTrashed()
                    ->whereHas('supplier', fn ($query) => $query->whereIn('type', Supplier::MANUAL_CONSIGNMENT_TYPES))
                    ->orderByDesc('sent_date')
                    ->orderByDesc('id')
                    ->get(['id', 'public_id', 'supplier_id', 'sent_date'])
            ),
        ]);
    }

    public function labelIndex(Request $request): View
    {
        $term = trim($request->string('term')->toString());
        $perPage = $this->resolvePerPage($request);

        $products = Product::query()
            ->select(['id', 'public_id', 'consignment_note_id', 'supplier_id', 'image_path', 'name', 'sale_price', 'quantity', 'returned_at', 'created_at'])
            ->with([
                'supplier:id,public_id,name',
                'consignmentNote:id,public_id,supplier_id,sent_date',
            ])
            ->sellable()
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($innerQuery) use ($term): void {
                    $innerQuery->where('name', 'like', '%'.$term.'%')
                        ->orWhere('public_id', 'like', '%'.$term.'%')
                        ->orWhereHas('supplier', function ($supplierQuery) use ($term): void {
                            $supplierQuery->where('name', 'like', '%'.$term.'%')
                                ->orWhere('public_id', 'like', '%'.$term.'%');
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $sendSummaryMap = $this->resolveSendSummaries(
            $products->getCollection()->pluck('supplier_id')->unique()->all()
        );

        $products->setCollection(
            $products->getCollection()->map(function (Product $product) use ($sendSummaryMap): Product {
                $sendSummary = $sendSummaryMap[$product->consignment_note_id] ?? [
                    'round' => 1,
                    'days' => 0,
                    'label' => 'Lần 1 / 0 ngày / ---',
                ];

                $product->setAttribute('send_round', $sendSummary['round']);
                $product->setAttribute('send_days', $sendSummary['days']);
                $product->setAttribute('send_summary', $sendSummary['label']);
                $product->setAttribute('label_code', $this->buildLabelCode($product, $sendSummary['round']));
                $product->setAttribute('barcode_payload', (string) $product->id);

                return $product;
            })
        );

        return view('products.label-index', [
            'products' => $products,
        ]);
    }

    public function printLabels(Request $request): View
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:products,id'],
        ]);

        $selectedIds = array_map('intval', $validated['ids']);

        $products = Product::query()
            ->select(['id', 'public_id', 'consignment_note_id', 'supplier_id', 'name', 'sale_price', 'quantity', 'returned_at', 'created_at'])
            ->with([
                'supplier:id,public_id,name',
                'consignmentNote:id,public_id,supplier_id,sent_date',
            ])
            ->sellable()
            ->whereIn('id', $selectedIds)
            ->get()
            ->sortBy(fn (Product $product): int => array_search($product->id, $selectedIds, true) ?: 0)
            ->values();

        $sendSummaryMap = $this->resolveSendSummaries(
            $products->pluck('supplier_id')->unique()->all()
        );

        $products = $products->map(function (Product $product) use ($sendSummaryMap): Product {
            $sendSummary = $sendSummaryMap[$product->consignment_note_id] ?? [
                'round' => 1,
                'days' => 0,
                'label' => 'Lần 1 / 0 ngày / ---',
            ];

            $product->setAttribute('send_round', $sendSummary['round']);
            $product->setAttribute('send_days', $sendSummary['days']);
            $product->setAttribute('send_summary', $sendSummary['label']);
            $product->setAttribute('label_code', $this->buildLabelCode($product, $sendSummary['round']));
            $product->setAttribute('barcode_payload', (string) $product->id);
            $product->setAttribute('barcode_svg', $this->generateBarcode((string) $product->id));

            return $product;
        });

        return view('products.label-print', [
            'products' => $products,
        ]);
    }

    public function label(Product $product): View
    {
        abort_if(! $product->isSellable(), 404);

        $barcodeData = $this->buildProductBarcodeData($product);

        return view('products.label', [
            'product' => $product,
            'sendSummary' => $barcodeData['sendSummary'],
            'barcodeSvg' => $this->generateBarcode($barcodeData['barcodePayload']),
            'barcodePayload' => $barcodeData['labelCode'],
        ]);
    }

    public function barcode(Product $product)
    {
        abort_if(! $product->isSellable(), 404);

        $barcodeData = $this->buildProductBarcodeData($product);

        $svg = $this->generateBarcode($barcodeData['barcodePayload']);

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="product-'.$product->public_id.'-barcode.svg"');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        if ($response = $this->preflightImageUpload($request, 'update', $product)) {
            return $response;
        }

        // Pre-check uploaded file for PHP upload errors to log helpful diagnostics
        if ($file = $this->resolveUploadedImage($request)) {
            $error = $file->getError();
            if ($error !== UPLOAD_ERR_OK) {
                Log::error('Product image upload error (update)', [
                    'error_code' => $error,
                    'product_id' => $product->id,
                    'file_info' => is_object($file) ? [
                        'clientName' => $file->getClientOriginalName(),
                        'clientMime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ] : null,
                    'files' => $_FILES,
                ]);

                return redirect()->back()->withInput()->withErrors(['image' => 'File upload error (code '.$error.'). Vui lòng thử lại.']);
            }
        } else {
            if ($this->requestHasUnexpectedImageFileEntries()) {
                Log::warning('No image file detected in request, but $_FILES is not empty (update)', ['files' => $_FILES, 'product_id' => $product->id]);
            }
        }

        $validated = $this->validatedData($request);
        $data = $validated['data'];
        $supplier = $validated['supplier'];

        $image = $this->resolveUploadedImage($request);

        if ($image instanceof UploadedFile) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $this->storeOptimizedImage($image);
        }

        $result = DB::transaction(function () use ($data, $product, $request, $supplier): array {
            $consignmentNote = $this->resolveConsignmentNoteForProduct($supplier, $request, $data, $product);
            $data['consignment_note_id'] = $consignmentNote->id;

            return [
                'product' => $this->products->update($product->id, $data),
                'consignmentNote' => $consignmentNote,
            ];
        });

        /** @var \App\Models\ConsignmentNote $consignmentNote */
        $consignmentNote = $result['consignmentNote'];

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'products.update',
            'method' => $request->method(),
            'route_name' => 'products.update',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'product_id' => $product->id,
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'supplier_id' => $data['supplier_id'],
                'consignment_note_id' => $consignmentNote->id,
                'quantity' => $data['quantity'],
            ],
        ]);

        return redirect()->route('products.index')->with('status', 'Đã cập nhật sản phẩm.');
    }

    public function returnToSupplier(Request $request, Product $product): RedirectResponse
    {
        if ($product->isReturned()) {
            return redirect()->route('products.index')->with('status', 'Sản phẩm này đã được trả cho người gửi rồi.');
        }

        $product->forceFill([
            'returned_at' => now(),
            'returned_by_id' => $request->user()?->id,
            'quantity' => 0,
        ])->save();

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'products.return',
            'method' => $request->method(),
            'route_name' => 'products.return',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'supplier_id' => $product->supplier_id,
                'consignment_note_id' => $product->consignment_note_id,
                'returned_at' => now()->toDateTimeString(),
            ],
        ]);

        return redirect()->route('products.index')->with('status', 'Đã đánh dấu sản phẩm đã trả cho người gửi. Sản phẩm này sẽ không bán được nữa.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $payload = [
            'product_id' => $product->id,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'supplier_id' => $product->supplier_id,
            'consignment_note_id' => $product->consignment_note_id,
            'quantity' => $product->quantity,
        ];

        $this->products->delete($product->id);

        AuditLog::record([
            'user_id' => request()->user()?->id,
            'action' => 'products.destroy',
            'method' => request()->method(),
            'route_name' => 'products.destroy',
            'path' => request()->path(),
            'status_code' => 302,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => $payload,
        ]);

        return redirect()->route('products.index')->with('status', 'Đã đưa sản phẩm vào thùng rác.');
    }

    private function preflightImageUpload(Request $request, string $context, ?Product $product = null): ?RedirectResponse
    {
        $image = $this->resolveUploadedImage($request);

        if ($image instanceof UploadedFile && ! $image->isValid()) {
            $this->logInvalidImageUpload($image, $context, $product);

            return redirect()->back()->withInput()->withErrors([
                'image' => $this->imageUploadErrorMessage($image->getError()),
            ]);
        }

        if ($image instanceof UploadedFile) {
            return null;
        }

        foreach (['camera_image', 'image'] as $field) {
            $candidate = $request->file($field);

            if (! $candidate instanceof UploadedFile) {
                continue;
            }

            if ($candidate->isValid()) {
                continue;
            }

            $this->logInvalidImageUpload($candidate, $context, $product, $field);

            return redirect()->back()->withInput()->withErrors([
                'image' => $this->imageUploadErrorMessage($candidate->getError()),
            ]);
        }

        if ($this->requestHasUnexpectedImageFileEntries()) {
            Log::warning('No image file detected in request, but $_FILES is not empty ('.$context.')', array_filter([
                'files' => $_FILES,
                'product_id' => $product?->id,
            ], static fn ($value) => $value !== null));
        }

        return null;
    }

    private function resolveUploadedImage(Request $request): ?UploadedFile
    {
        $image = $request->file('image');

        if ($image instanceof UploadedFile) {
            return $image;
        }

        $cameraImage = $request->file('camera_image');

        return $cameraImage instanceof UploadedFile ? $cameraImage : null;
    }

    private function logInvalidImageUpload(UploadedFile $image, string $context, ?Product $product = null, string $field = 'image'): void
    {
        Log::error('Product image upload error ('.$context.')', array_filter([
            'field' => $field,
            'error_code' => $image->getError(),
            'product_id' => $product?->id,
            'file_info' => [
                'clientName' => $image->getClientOriginalName(),
                'clientMime' => $image->getClientMimeType(),
                'size' => $image->getSize(),
            ],
            'files' => $_FILES,
        ], static fn ($value) => $value !== null));
    }

    private function imageUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ảnh vượt quá giới hạn tải lên. Vui lòng chọn ảnh nhỏ hơn rồi thử lại.',
            UPLOAD_ERR_PARTIAL => 'Ảnh tải lên chưa hoàn tất. Vui lòng thử lại.',
            UPLOAD_ERR_NO_FILE => 'Chưa có ảnh nào được chọn để tải lên.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'Máy chủ không thể nhận ảnh lúc này. Vui lòng thử lại sau.',
            default => 'Ảnh tải lên không thành công. Vui lòng thử lại.',
        };
    }

    private function requestHasUnexpectedImageFileEntries(): bool
    {
        foreach ($_FILES as $file) {
            if (! is_array($file)) {
                continue;
            }

            $errors = is_array($file['error'] ?? null) ? $file['error'] : [$file['error'] ?? UPLOAD_ERR_NO_FILE];
            $names = is_array($file['name'] ?? null) ? $file['name'] : [$file['name'] ?? ''];

            foreach ($errors as $index => $error) {
                $name = $names[$index] ?? '';

                if ((int) $error !== UPLOAD_ERR_NO_FILE || $name !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'consignment_note_id' => ['nullable', 'exists:consignment_notes,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            // Accept any file upload without MIME validation to avoid mobile upload errors.
            'image' => ['nullable', 'file'],
            'camera_image' => ['nullable', 'file'],
        ], [
            'image.uploaded' => 'Ảnh tải lên không thành công. Vui lòng thử lại hoặc chọn ảnh nhỏ hơn.',
            'image.file' => 'Tệp ảnh không hợp lệ.',
            'camera_image.uploaded' => 'Ảnh tải lên không thành công. Vui lòng thử lại hoặc chọn ảnh nhỏ hơn.',
            'camera_image.file' => 'Tệp ảnh không hợp lệ.',
        ]);

        unset($data['image'], $data['camera_image']);

        $supplier = Supplier::query()->withTrashed()->findOrFail($data['supplier_id']);

        if ($supplier->requiresManualConsignment()) {
            $consignmentNoteId = $data['consignment_note_id'] ?? null;

            if ($consignmentNoteId === null) {
                throw ValidationException::withMessages([
                    'consignment_note_id' => 'Vui lòng chọn phiếu ký gửi cho nhà cung cấp này.',
                ]);
            }

            $belongsToSupplier = ConsignmentNote::query()->withTrashed()
                ->whereKey($consignmentNoteId)
                ->where('supplier_id', $supplier->id)
                ->exists();

            if (! $belongsToSupplier) {
                throw ValidationException::withMessages([
                    'consignment_note_id' => 'Phiếu ký gửi không thuộc nhà cung cấp đã chọn.',
                ]);
            }
        }

        return [
            'data' => $data,
            'supplier' => $supplier,
        ];
    }

    private function resolveConsignmentNoteForProduct(
        Supplier $supplier,
        Request $request,
        array $data,
        ?Product $product = null
    ): ConsignmentNote
    {
        if ($supplier->requiresManualConsignment()) {
            return ConsignmentNote::query()->withTrashed()
                ->whereKey($data['consignment_note_id'])
                ->where('supplier_id', $supplier->id)
                ->firstOrFail();
        }

        return $this->resolveAutoGeneratedConsignmentNote($supplier, $request, $data, $product);
    }

    private function resolveAutoGeneratedConsignmentNote(
        Supplier $supplier,
        Request $request,
        array $data,
        ?Product $product = null
    ): ConsignmentNote {
        $existingConsignment = null;

        if ($product !== null) {
            $product->loadMissing('consignmentNote');

            if (
                $product->consignmentNote
                && $product->consignmentNote->isAutoGenerated()
                && (int) $product->consignmentNote->supplier_id === (int) $supplier->id
            ) {
                $existingConsignment = $product->consignmentNote;
            }
        }

        $attributes = [
            'responsible_user_id' => $request->user()?->id,
            'responsible_name' => $request->user()?->name ?? $supplier->responsible_name ?? $supplier->name,
            'supplier_id' => $supplier->id,
            'quantity' => (int) $data['quantity'],
            'notes' => ConsignmentNote::AUTO_GENERATED_NOTE_MARKER,
        ];

        if ($existingConsignment !== null) {
            $existingConsignment->fill($attributes);
            $existingConsignment->save();

            return $existingConsignment->fresh();
        }

        return ConsignmentNote::query()->create($attributes + [
            'sent_date' => now()->toDateString(),
        ]);
    }

    /**
     * @param  array<int>  $supplierIds
     * @return array<int, int>
     */
    private function resolveSendSummaries(array $supplierIds): array
    {
        if ($supplierIds === []) {
            return [];
        }

        $sendSummaries = [];

        $consignmentNotes = ConsignmentNote::query()->withTrashed()
            ->whereIn('supplier_id', $supplierIds)
            ->orderBy('supplier_id')
            ->orderBy('sent_date')
            ->orderBy('id')
            ->get(['id', 'supplier_id', 'sent_date']);

        $lastBySupplier = [];

        foreach ($consignmentNotes as $note) {
            $supplierId = (int) $note->supplier_id;
            $sentDate = $note->sent_date;

            if (! isset($lastBySupplier[$supplierId])) {
                $sendSummaries[$note->id] = [
                    'round' => 1,
                    'days' => 0,
                    'label' => 'Lần 1 / 0 ngày / '.$sentDate->format('d/m/Y'),
                ];
                $lastBySupplier[$supplierId] = [
                    'date' => $sentDate,
                    'round' => 1,
                ];

                continue;
            }

            $lastDate = $lastBySupplier[$supplierId]['date'];
            $currentRound = $lastBySupplier[$supplierId]['round'];
            $daysSincePrevious = $lastDate->diffInDays($sentDate);

            if ($daysSincePrevious <= 15) {
                $sendSummaries[$note->id] = [
                    'round' => $currentRound,
                    'days' => $daysSincePrevious,
                    'label' => 'Lần '.$currentRound.' / '.$daysSincePrevious.' ngày / '.$sentDate->format('d/m/Y'),
                ];
            } else {
                $currentRound++;
                $sendSummaries[$note->id] = [
                    'round' => $currentRound,
                    'days' => $daysSincePrevious,
                    'label' => 'Lần '.$currentRound.' / '.$daysSincePrevious.' ngày / '.$sentDate->format('d/m/Y'),
                ];
            }

            $lastBySupplier[$supplierId] = [
                'date' => $sentDate,
                'round' => $currentRound,
            ];
        }

        return $sendSummaries;
    }

    /**
     * @return array{round:int,days:int,label:string}
     */
    private function resolveProductSendSummary(Product $product): array
    {
        $sendSummaries = $this->resolveSendSummaries([(int) $product->supplier_id]);

        return $sendSummaries[$product->consignment_note_id] ?? [
            'round' => 1,
            'days' => 0,
            'label' => 'Lần 1 / 0 ngày / ---',
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function consignmentExpiryWindows(): array
    {
        return [
            now()->subDays(Product::CONSIGNMENT_TERM_DAYS)->toDateString(),
            now()->subDays(Product::CONSIGNMENT_TERM_DAYS - Product::CONSIGNMENT_WARNING_DAYS)->toDateString(),
        ];
    }

    /**
     * @return array{pending:int,expiring_soon:int,expired:int,returned:int}
     */
    private function resolveConsignmentExpirySummary(?int $supplierId = null): array
    {
        [$warningWindowStart, $warningWindowEnd] = $this->consignmentExpiryWindows();

        $baseQuery = function () use ($supplierId): Builder {
            return Product::query()
                ->when($supplierId !== null, function (Builder $query) use ($supplierId): void {
                    $query->where('supplier_id', $supplierId);
                });
        };

        return [
            'pending' => $baseQuery()
                ->whereNull('returned_at')
                ->where('quantity', '>', 0)
                ->whereHas('consignmentNote', function (Builder $query) use ($warningWindowEnd): void {
                    $query->whereDate('sent_date', '<=', $warningWindowEnd);
                })
                ->count(),
            'expiring_soon' => $baseQuery()
                ->whereNull('returned_at')
                ->where('quantity', '>', 0)
                ->whereHas('consignmentNote', function (Builder $query) use ($warningWindowStart, $warningWindowEnd): void {
                    $query->whereBetween('sent_date', [$warningWindowStart, $warningWindowEnd]);
                })
                ->count(),
            'expired' => $baseQuery()
                ->whereNull('returned_at')
                ->where('quantity', '>', 0)
                ->whereHas('consignmentNote', function (Builder $query) use ($warningWindowStart): void {
                    $query->whereDate('sent_date', '<', $warningWindowStart);
                })
                ->count(),
            'returned' => $baseQuery()
                ->whereNotNull('returned_at')
                ->count(),
        ];
    }

    /**
     * @return array{sendSummary:array{round:int,days:int,label:string},barcodePayload:string,labelCode:string}
     */
    private function buildProductBarcodeData(Product $product): array
    {
        $product->loadMissing([
            'supplier:id,public_id,name',
            'consignmentNote:id,public_id,supplier_id,sent_date',
        ]);

        $sendSummary = $this->resolveProductSendSummary($product);
        $labelCode = $this->buildLabelCode($product, $sendSummary['round']);

        return [
            'sendSummary' => $sendSummary,
            'barcodePayload' => (string) $product->id,
            'labelCode' => $labelCode,
        ];
    }

    private function generateBarcode(string $value): string
    {
        $generator = new BarcodeGeneratorSVG();
        return $generator->getBarcode($value, BarcodeGeneratorSVG::TYPE_CODE_128);
    }

    private function buildLabelCode(Product $product, int $sendRound): string
    {
        return $product->id.'-'.$product->supplier_id.'-'.$sendRound;
    }

    /**
     * @return array<int, array{value:int,label:string}>
     */
    private function buildConsignmentOptions(Collection $consignments): array
    {
        $sendSummaries = $this->resolveSendSummaries(
            $consignments->pluck('supplier_id')->unique()->all()
        );

        return $consignments
            ->map(function (ConsignmentNote $consignment) use ($sendSummaries): array {
                $summary = $sendSummaries[$consignment->id] ?? [
                    'label' => 'Lần 1 / 0 ngày / '.optional($consignment->sent_date)->format('d/m/Y'),
                ];

                return [
                    'value' => $consignment->id,
                    'supplier_id' => $consignment->supplier_id,
                    'label' => '#'.$consignment->public_id.' · '.$summary['label'],
                ];
            })
            ->values()
            ->all();
    }

    private function storeOptimizedImage(UploadedFile $image): string
    {
        try {
            Storage::disk('public')->makeDirectory('products');

            $ext = $image->getClientOriginalExtension() ?: $image->extension();
            $filename = (string) Str::uuid().'.'.$ext;

            // storeAs returns the relative path within the disk
            $path = $image->storeAs('products', $filename, 'public');

            if (! $path) {
                throw new \RuntimeException('Failed to store uploaded image (storeAs returned falsy).');
            }

            return $path;
        } catch (\Throwable $e) {
            Log::error('Failed to store product image', [
                'message' => $e->getMessage(),
                'exception' => $e,
                'clientName' => $image->getClientOriginalName(),
                'clientMime' => $image->getClientMimeType(),
                'size' => $image->getSize(),
            ]);

            throw $e;
        }
    }
}
