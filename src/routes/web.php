<?php

use App\Http\Controllers\Auth\LoginController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Backoffice\StockEntryController;
use App\Http\Controllers\Backoffice\PurchaseOrderController;
# como quiero controlarlo todo por separado no he creado resources, y eso me obliga a importar lo siguiente
use App\Http\Controllers\backoffice\CategoryController;
use App\Http\Controllers\backoffice\ProductController;
# esto es para un log global de actividad para no perder el control de los productos borrados
use App\Http\Controllers\Backoffice\ActivityLogController;
# esto para la creacion de usuarios
use App\Http\Controllers\Backoffice\UserController;
# para el control de las devoluciones
use App\Http\Controllers\Backoffice\PurchaseOrderReturnController;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;

use App\Models\ProductRate;

Route::get('/', function () {
    return redirect()->route('backoffice.dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::prefix('backoffice')->middleware('auth')->group(function () {
    Route::get('/', function () {
        $today = now()->toDateString();
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $nextSevenDays = now()->copy()->addDays(7)->toDateString();

        $productsCount = Product::count();
        $categoriesCount = Category::count();

        $ordersToday = PurchaseOrder::whereDate('order_date', $today)->count();
        $revenueToday = (float) PurchaseOrder::whereDate('order_date', $today)->sum('total_price');

        $ordersThisMonth = PurchaseOrder::whereMonth('order_date', $currentMonth)
            ->whereYear('order_date', $currentYear)
            ->count();

        $revenueThisMonth = (float) PurchaseOrder::whereMonth('order_date', $currentMonth)
            ->whereYear('order_date', $currentYear)
            ->sum('total_price');

        $unitsThisMonth = (int) PurchaseOrder::whereMonth('order_date', $currentMonth)
            ->whereYear('order_date', $currentYear)
            ->sum('units');

        $averageTicketThisMonth = $ordersThisMonth > 0
            ? $revenueThisMonth / $ordersThisMonth
            : 0;

        $latestOrders = PurchaseOrder::with('product')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $topProductThisMonth = PurchaseOrder::select(
                'product_id',
                DB::raw('SUM(units) as total_units'),
                DB::raw('SUM(total_price) as total_revenue')
            )
            ->with('product')
            ->whereMonth('order_date', $currentMonth)
            ->whereYear('order_date', $currentYear)
            ->groupBy('product_id')
            ->orderByDesc('total_units')
            ->first();

        $productsWithoutImagesCount = Product::doesntHave('images')->count();

        $productsWithoutActiveRateCount = Product::whereDoesntHave('rates', function ($query) use ($today) {
            $query->whereDate('start_date', '<=', $today)
                ->where(function ($subQuery) use ($today) {
                    $subQuery->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $today);
                });
        })->count();

        $categoriesWithoutProductsCount = Category::doesntHave('products')->count();

        $ratesExpiringSoonCount = ProductRate::whereNotNull('end_date')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $nextSevenDays)
            ->count();

        $ratesExpiringSoon = ProductRate::with('product')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $nextSevenDays)
            ->orderBy('end_date')
            ->take(5)
            ->get();

        $latestProducts = Product::withCount(['images', 'rates', 'categories'])
            ->latest()
            ->take(5)
            ->get();

        return view('backoffice.dashboard', compact(
            'productsCount',
            'categoriesCount',
            'ordersToday',
            'revenueToday',
            'ordersThisMonth',
            'revenueThisMonth',
            'unitsThisMonth',
            'averageTicketThisMonth',
            'latestOrders',
            'topProductThisMonth',
            'productsWithoutImagesCount',
            'productsWithoutActiveRateCount',
            'categoriesWithoutProductsCount',
            'ratesExpiringSoonCount',
            'ratesExpiringSoon',
            'latestProducts'
        ));
    })->name('backoffice.dashboard');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/categories', [CategoryController::class, 'index'])
        ->middleware('permission:categories_view')
        ->name('categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])
        ->middleware('permission:categories_manage')
        ->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('permission:categories_manage')
        ->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])
        ->middleware('permission:categories_manage')
        ->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])
        ->middleware('permission:categories_manage')
        ->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('permission:categories_delete')
        ->name('categories.destroy');

    
    Route::get('/products', [ProductController::class, 'index'])
        ->middleware('permission:products_view')
        ->name('products.index');
    Route::post('/products/filters/save', [ProductController::class, 'saveFilters'])
        ->middleware('permission:products_view')
        ->name('products.filters.save');
    Route::post('/products/filters/clear', [ProductController::class, 'clearSavedFilters'])
        ->middleware('permission:products_view')
        ->name('products.filters.clear');
    Route::get('/products/create', [ProductController::class, 'create'])
        ->middleware('permission:products_manage')
        ->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])
        ->middleware('permission:products_manage')
        ->name('products.store');
    Route::get('/products/{product}/history', [ProductController::class, 'history'])
        ->middleware('permission:products_view')
        ->name('products.history');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])
        ->middleware('permission:products_manage')
        ->name('products.edit');
    Route::get('/products/{product}/duplicate', [ProductController::class, 'duplicate'])
        ->middleware('permission:products_manage')
        ->name('products.duplicate');
    Route::put('/products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:products_manage')
        ->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:products_delete')
        ->name('products.destroy');
    Route::post('/products/{productId}/restore', [ProductController::class, 'restore'])
        ->middleware('permission:products_delete')
        ->name('products.restore');
    Route::get('/products/trash', [ProductController::class, 'trash'])
        ->middleware('permission:products_delete')
        ->name('products.trash');
    Route::get('/products/export/xls', [ProductController::class, 'exportXls'])
        ->middleware('permission:products_view')
        ->name('products.export.xls');
    Route::get('/products/{product}/pdf', [ProductController::class, 'exportPdf'])
        ->middleware('permission:products_view')
        ->name('products.export.pdf');

    Route::get('/calendar', [PurchaseOrderController::class, 'index'])
        ->middleware('permission:calendar_view')
        ->name('calendar.index');
    Route::get('/calendar/create', [PurchaseOrderController::class, 'create'])
        ->middleware('permission:calendar_manage')
        ->name('calendar.create');
    Route::post('/calendar', [PurchaseOrderController::class, 'store'])
        ->middleware('permission:calendar_manage')
        ->name('calendar.store');
    Route::get('/calendar/export/xls', [PurchaseOrderController::class, 'exportXls'])
        ->middleware('permission:calendar_view')
        ->name('calendar.export.xls');
    Route::get('/calendar/export/pdf', [PurchaseOrderController::class, 'exportPdf'])
        ->middleware('permission:calendar_view')
        ->name('calendar.export.pdf');
    Route::get('/calendar/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])
        ->middleware('permission:calendar_manage')
        ->name('calendar.edit');
    Route::put('/calendar/{purchaseOrder}', [PurchaseOrderController::class, 'update'])
        ->middleware('permission:calendar_manage')
        ->name('calendar.update');
    Route::delete('/calendar/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])
        ->middleware('permission:calendar_delete')
        ->name('calendar.destroy');

    Route::get('/activity', [ActivityLogController::class, 'index'])
        ->middleware('permission:activity_view')
        ->name('activity.index');
    Route::get('/activity/products/{productId}', [ActivityLogController::class, 'productHistory'])
        ->middleware('permission:activity_view')
        ->name('activity.products.history');
    Route::get('/calendar/{purchaseOrder}/returns/create', [PurchaseOrderReturnController::class, 'create'])
        ->middleware('permission:calendar_manage')
        ->name('purchase-order-returns.create');
    Route::post('/calendar/{purchaseOrder}/returns', [PurchaseOrderReturnController::class, 'store'])
        ->middleware('permission:calendar_manage')
        ->name('purchase-order-returns.store');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users_manage')
        ->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('permission:users_manage')
        ->name('users.create');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:users_manage')
        ->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users_manage')
        ->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users_manage')
        ->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users_manage')
        ->name('users.destroy');

    Route::get('/stock-entries', [StockEntryController::class, 'index'])
        ->middleware('permission:products_view')
        ->name('stock-entries.index');
    Route::get('/stock-entries/create', [StockEntryController::class, 'create'])
        ->middleware('permission:products_manage')
        ->name('stock-entries.create');
    Route::post('/stock-entries', [StockEntryController::class, 'store'])
        ->middleware('permission:products_manage')
        ->name('stock-entries.store');
});

