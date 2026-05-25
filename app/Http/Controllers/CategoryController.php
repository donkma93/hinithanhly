<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryRepositoryInterface $categories)
    {
        $this->middleware('permission:categories.view')->only('index');
        $this->middleware('permission:categories.create|categories.manage')->only('store');
        $this->middleware('permission:categories.update|categories.manage')->only('update');
        $this->middleware('permission:categories.delete|categories.manage')->only('destroy');
    }

    public function index(Request $request): View
    {
        $publicId = trim($request->string('public_id')->toString());

        return view('categories.index', [
            'categories' => Category::query()
                ->select(['id', 'public_id', 'name', 'description', 'is_active', 'created_at'])
                ->when($publicId !== '', fn ($query) => $query->where('public_id', $publicId))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->categories->create($data + ['is_active' => true]);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'categories.store',
            'method' => $request->method(),
            'route_name' => 'categories.store',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ],
        ]);

        return redirect()->route('categories.index')->with('status', 'Đã thêm danh mục.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->categories->update($category->id, $data);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'categories.update',
            'method' => $request->method(),
            'route_name' => 'categories.update',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'category_id' => $category->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ],
        ]);

        return redirect()->route('categories.index')->with('status', 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $payload = [
            'category_id' => $category->id,
            'name' => $category->name,
        ];

        $this->categories->delete($category->id);

        AuditLog::record([
            'user_id' => request()->user()?->id,
            'action' => 'categories.destroy',
            'method' => request()->method(),
            'route_name' => 'categories.destroy',
            'path' => request()->path(),
            'status_code' => 302,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => $payload,
        ]);

        return redirect()->route('categories.index')->with('status', 'Đã xoá danh mục.');
    }
}