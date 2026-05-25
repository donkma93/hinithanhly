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
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
        $this->middleware('permission:products.view')->only('index');
        $this->middleware('permission:products.create|products.manage')->only('store');
        $this->middleware('permission:products.update|products.manage')->only('update');
        $this->middleware('permission:products.delete|products.manage')->only('destroy');
    }

    public function index(Request $request): View
    {
        $publicId = trim($request->string('public_id')->toString());

        return view('products.index', [
            'products' => Product::query()
                ->select(['id', 'public_id', 'consignment_note_id', 'supplier_id', 'category_id', 'created_by_id', 'name', 'sale_price', 'quantity', 'image_path', 'description', 'created_at'])
                ->with([
                    'category:id,public_id,name',
                    'supplier:id,public_id,name',
                    'consignmentNote:id,public_id,sent_date',
                ])
                ->when($publicId !== '', fn ($query) => $query->where('public_id', $publicId))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(['id', 'public_id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'public_id', 'name']),
            'consignments' => ConsignmentNote::query()->latest()->get(['id', 'public_id', 'supplier_id', 'sent_date']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['created_by_id'] = $request->user()?->id;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $this->products->create($data);

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
                'consignment_note_id' => $data['consignment_note_id'],
                'quantity' => $data['quantity'],
            ],
        ]);

        return redirect()->route('products.index')->with('status', 'Đã thêm sản phẩm.');
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'consignments' => ConsignmentNote::query()->latest()->get(['id', 'supplier_id', 'sent_date']),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $this->products->update($product->id, $data);

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
                'consignment_note_id' => $data['consignment_note_id'],
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
        return $request->validate([
            'consignment_note_id' => ['required', 'exists:consignment_notes,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
    }
}