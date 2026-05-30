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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Picqer\Barcode\BarcodeGeneratorSVG;

class ProductController extends Controller
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
        $this->middleware('permission:products.view')->only(['index', 'labelIndex', 'printLabels', 'label', 'barcode']);
        $this->middleware('permission:products.create|products.manage')->only('store');
        $this->middleware('permission:products.update|products.manage')->only('update');
        $this->middleware('permission:products.delete')->only('destroy');
    }

    public function index(Request $request): View
    {
        $publicId = trim($request->string('public_id')->toString());
        $perPage = $this->resolvePerPage($request);

        $products = Product::query()
            ->select(['id', 'public_id', 'consignment_note_id', 'supplier_id', 'category_id', 'created_by_id', 'name', 'sale_price', 'quantity', 'image_path', 'description', 'created_at'])
            ->with([
                'category:id,public_id,name',
                'supplier:id,public_id,name',
                'consignmentNote:id,public_id,supplier_id,responsible_user_id,sent_date',
                'consignmentNote.responsibleUser:id,public_id,name',
            ])
            ->when($publicId !== '', fn ($query) => $query->where('public_id', $publicId))
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

        return view('products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(['id', 'public_id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'public_id', 'name', 'type']),
            'consignmentOptions' => $this->buildConsignmentOptions(
                ConsignmentNote::query()
                    ->whereHas('supplier', fn ($query) => $query->whereIn('type', Supplier::MANUAL_CONSIGNMENT_TYPES))
                    ->orderByDesc('sent_date')
                    ->orderByDesc('id')
                    ->get(['id', 'public_id', 'supplier_id', 'sent_date'])
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $data = $validated['data'];
        $supplier = $validated['supplier'];
        $data['created_by_id'] = $request->user()?->id;

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeOptimizedImage($request->file('image'));
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
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'public_id', 'name', 'type']),
            'consignmentOptions' => $this->buildConsignmentOptions(
                ConsignmentNote::query()
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
            ->select(['id', 'public_id', 'consignment_note_id', 'supplier_id', 'image_path', 'name', 'sale_price', 'quantity', 'created_at'])
            ->with([
                'supplier:id,public_id,name',
                'consignmentNote:id,public_id,supplier_id,sent_date',
            ])
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
            ->select(['id', 'public_id', 'consignment_note_id', 'supplier_id', 'name', 'sale_price', 'quantity', 'created_at'])
            ->with([
                'supplier:id,public_id,name',
                'consignmentNote:id,public_id,supplier_id,sent_date',
            ])
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
        $barcodeData = $this->buildProductBarcodeData($product);

        $svg = $this->generateBarcode($barcodeData['barcodePayload']);

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="product-'.$product->public_id.'-barcode.svg"');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $data = $validated['data'];
        $supplier = $validated['supplier'];

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $this->storeOptimizedImage($request->file('image'));
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

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

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

        return redirect()->route('products.index')->with('status', 'Đã xoá sản phẩm.');
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
            // Allow larger uploads from mobile devices (10MB) and validate image
            'image' => ['nullable', 'image', 'max:10240'],
        ]);

        $supplier = Supplier::query()->findOrFail($data['supplier_id']);

        if ($supplier->requiresManualConsignment()) {
            $consignmentNoteId = $data['consignment_note_id'] ?? null;

            if ($consignmentNoteId === null) {
                throw ValidationException::withMessages([
                    'consignment_note_id' => 'Vui lòng chọn phiếu ký gửi cho nhà cung cấp này.',
                ]);
            }

            $belongsToSupplier = ConsignmentNote::query()
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
    ): ConsignmentNote {
        if ($supplier->requiresManualConsignment()) {
            return ConsignmentNote::query()
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

        $consignmentNotes = ConsignmentNote::query()
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
        $binary = file_get_contents($image->getRealPath());

        if ($binary === false) {
            return $image->store('products', 'public');
        }

        $source = imagecreatefromstring($binary);

        if ($source === false) {
            return $image->store('products', 'public');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxSize = 1200;
        $scale = min(1, $maxSize / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($target === false) {
            imagedestroy($source);

            return $image->store('products', 'public');
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        // Ensure the products directory exists
        $productsDir = Storage::disk('public')->path('products');
        if (! is_dir($productsDir)) {
            mkdir($productsDir, 0755, true);
        }

        $fileName = Str::uuid()->toString().'.webp';
        $storagePath = 'products/'.$fileName;
        $fullPath = Storage::disk('public')->path($storagePath);

        if (! imagewebp($target, $fullPath, 60)) {
            $fileName = Str::uuid()->toString().'.jpg';
            $storagePath = 'products/'.$fileName;
            $fullPath = Storage::disk('public')->path($storagePath);

            $background = imagecreatetruecolor($targetWidth, $targetHeight);
            $white = imagecolorallocate($background, 255, 255, 255);
            imagefill($background, 0, 0, $white);
            imagecopy($background, $target, 0, 0, 0, 0, $targetWidth, $targetHeight);
            imagejpeg($background, $fullPath, 60);
            imagedestroy($background);
        }

        imagedestroy($target);
        imagedestroy($source);

        return $storagePath;
    }
}
