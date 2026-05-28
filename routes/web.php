<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ConsignmentNoteController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SupplierController;
use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('sales');
});

Route::get('/ban-hang', [SalesController::class, 'index'])->name('sales.index');
Route::get('/ban-hang/products/{code}', [SalesController::class, 'lookup'])
    ->where('code', '[0-9\-]+')
    ->name('sales.lookup');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $months = collect(range(5, 0))->map(function (int $offset) {
            $date = Carbon::now()->subMonths($offset)->startOfMonth();

            return [
                'label' => $date->format('m/Y'),
                'start' => $date->copy()->startOfMonth()->toDateString(),
                'end' => $date->copy()->endOfMonth()->toDateString(),
            ];
        });

        $productSeries = $months->map(function (array $month) {
            return Product::query()
                ->whereBetween('created_at', [$month['start'], $month['end']])
                ->count();
        })->values();

        $consignmentSeries = $months->map(function (array $month) {
            return ConsignmentNote::query()
                ->whereBetween('created_at', [$month['start'], $month['end']])
                ->count();
        })->values();

        $supplierTypeLabels = array_values(Supplier::TYPES);
        $supplierTypeSeries = collect(array_keys(Supplier::TYPES))->map(function (string $type) {
            return Supplier::query()->where('type', $type)->count();
        })->values();

        return view('dashboard', [
            'stats' => [
                'categories' => Category::count(),
                'suppliers' => Supplier::count(),
                'consignments' => ConsignmentNote::count(),
                'products' => Product::count(),
            ],
            'activeRole' => auth()->user()?->getRoleNames()->first() ?? 'staff',
            'chartLabels' => $months->pluck('label'),
            'productSeries' => $productSeries,
            'consignmentSeries' => $consignmentSeries,
            'supplierTypeLabels' => $supplierTypeLabels,
            'supplierTypeSeries' => $supplierTypeSeries,
        ]);
    })->middleware('permission:dashboard.view')->name('dashboard');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    Route::get('/consignments', [ConsignmentNoteController::class, 'index'])->name('consignments.index');
    Route::post('/consignments', [ConsignmentNoteController::class, 'store'])->name('consignments.store');
    Route::get('/consignments/{consignment}/edit', [ConsignmentNoteController::class, 'edit'])->name('consignments.edit');
    Route::put('/consignments/{consignment}', [ConsignmentNoteController::class, 'update'])->name('consignments.update');
    Route::delete('/consignments/{consignment}', [ConsignmentNoteController::class, 'destroy'])->name('consignments.destroy');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/in-ma-hang', [ProductController::class, 'labelIndex'])->name('product-labels.index');
    Route::post('/in-ma-hang/in', [ProductController::class, 'printLabels'])->name('product-labels.print');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/qr', [ProductController::class, 'qr'])->name('products.qr');
    Route::get('/products/{product}/label', [ProductController::class, 'label'])->name('products.label');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view|users.manage')->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create|users.manage')->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update|users.manage')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update|users.manage')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete|users.manage')->name('users.destroy');

    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.view|permissions.manage')->name('permissions.index');
    Route::post('/permissions', [PermissionController::class, 'store'])->middleware('permission:permissions.create|permissions.manage')->name('permissions.store');
    Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->middleware('permission:permissions.update|permissions.manage')->name('permissions.edit');
    Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.update|permissions.manage')->name('permissions.update');
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete|permissions.manage')->name('permissions.destroy');

    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:permissions.view|permissions.manage')->name('roles.index');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:permissions.update|permissions.manage')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:permissions.update|permissions.manage')->name('roles.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/logs', [AuditLogController::class, 'index'])
        ->middleware('permission:logs.view')
        ->name('logs.index');

    // Sales checkout endpoint (requires auth)
    Route::post('/ban-hang/create-payment', [SalesController::class, 'createPayment'])->name('sales.createPayment');
    Route::post('/ban-hang/checkout', [SalesController::class, 'checkout'])->name('sales.checkout');

    // Settings (payment)
    Route::get('/settings/payment', [\App\Http\Controllers\SettingsController::class, 'editPayment'])
        ->middleware('permission:settings.manage')
        ->name('settings.payment.edit');

    Route::post('/settings/payment', [\App\Http\Controllers\SettingsController::class, 'updatePayment'])
        ->middleware('permission:settings.manage')
        ->name('settings.payment.update');
});

require __DIR__.'/auth.php';
