<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LowStockController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\OutletController;
use App\Http\Controllers\OutletPortalController;
use App\Http\Controllers\OutletReturnManagementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockMutationController;
use App\Http\Controllers\StockReportController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserManagementController;
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

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.process');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:Manager,Admin Prolog,Kepala Logistik'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::get('/reports/stock', [StockReportController::class, 'index'])
        ->name('reports.stock');
    Route::get('/reports/daily', [StockReportController::class, 'daily'])
        ->name('reports.daily');
    Route::get('/statistics', [StatisticsController::class, 'index'])
        ->name('statistics.index');
    Route::get('/notifications/low-stock', [LowStockController::class, 'index'])
        ->name('notifications.low-stock');
    Route::get('/settings', [SettingsController::class, 'index'])
        ->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])
        ->name('settings.profile.update');
});

Route::middleware(['auth', 'role:Manager'])->group(function () {
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])
        ->name('settings.password.update');
});

Route::middleware(['auth', 'role:Manager'])->group(function () {
    Route::resource('outlets', OutletController::class)->except(['show']);
    Route::resource('users', UserManagementController::class)
        ->except(['show']);

    Route::patch(
        '/users/{user}/toggle-status',
        [UserManagementController::class, 'toggleStatus']
    )->name('users.toggle-status');
});

Route::middleware(['auth', 'role:Manager'])->group(function () {
    Route::resource('suppliers', SupplierController::class);
    Route::resource('products', ProductController::class);
});

Route::middleware(['auth', 'role:Manager,Admin Prolog'])->group(function () {
    Route::get('/distributions', function () {
        return redirect()->route('warehouse.receipts.index');
    })->name('distributions.index');

    Route::get('/warehouse/orders', [OrderManagementController::class, 'index'])
        ->name('warehouse.orders.index');
    Route::patch('/warehouse/orders/{order}/review', [OrderManagementController::class, 'review'])
        ->name('warehouse.orders.review');
    Route::post('/warehouse/orders/{order}/dispatch', [OrderManagementController::class, 'dispatch'])
        ->name('warehouse.orders.dispatch');
    Route::get('/warehouse/receipts', [OrderManagementController::class, 'receipts'])
        ->name('warehouse.receipts.index');
    Route::post('/warehouse/receipts/{receipt}/validate', [OrderManagementController::class, 'validateReceipt'])
        ->name('warehouse.receipts.validate');

    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])
        ->name('stock-adjustments.index');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])
        ->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])
        ->name('stock-adjustments.store');

    Route::get('/stock-in', [StockTransactionController::class, 'stockIn'])
        ->name('stock-in.index');
    Route::get('/stock-in/create', [StockTransactionController::class, 'createStockIn'])
        ->name('stock-in.create');
    Route::post('/stock-in', [StockTransactionController::class, 'storeStockIn'])
        ->name('stock-in.store');

    Route::get('/stock-out', [StockTransactionController::class, 'stockOut'])
        ->name('stock-out.index');
    Route::get('/stock-out/create', [StockTransactionController::class, 'createStockOut'])
        ->name('stock-out.create');
    Route::post('/stock-out', [StockTransactionController::class, 'storeStockOut'])
        ->name('stock-out.store');

    Route::get('/stock-mutations', [StockMutationController::class, 'index'])
        ->name('stock-mutations.index');
});

Route::middleware(['auth', 'role:Manager,Admin Prolog,Kepala Logistik'])->group(function () {
    Route::get('/warehouse/returns', [OutletReturnManagementController::class, 'index'])
        ->name('warehouse.returns.index');
});

Route::middleware(['auth', 'role:Manager,Kepala Logistik'])->group(function () {
    Route::patch('/warehouse/returns/{return}/review', [OutletReturnManagementController::class, 'review'])
        ->name('warehouse.returns.review');
});

Route::middleware(['auth', 'role:Manager,Admin Prolog'])->group(function () {
    Route::post('/warehouse/returns/{return}/verify', [OutletReturnManagementController::class, 'verify'])
        ->name('warehouse.returns.verify');
});

Route::middleware(['auth', 'role:User Outlet'])->prefix('outlet')->name('outlet.')->group(function () {
    Route::get('/dashboard', [OutletPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/stocks', [OutletPortalController::class, 'stocks'])->name('stocks.index');
    Route::get('/orders', [OutletPortalController::class, 'orders'])->name('orders.index');
    Route::get('/orders/create', [OutletPortalController::class, 'createOrder'])->name('orders.create');
    Route::post('/orders', [OutletPortalController::class, 'storeOrder'])->name('orders.store');
    Route::post('/orders/{order}/receipt', [OutletPortalController::class, 'submitReceipt'])
        ->name('orders.receipt');
    Route::get('/returns', [OutletPortalController::class, 'returns'])->name('returns.index');
    Route::get('/returns/create', [OutletPortalController::class, 'createReturn'])->name('returns.create');
    Route::post('/returns', [OutletPortalController::class, 'storeReturn'])->name('returns.store');
    Route::get('/stocktake', [OutletPortalController::class, 'stocktake'])->name('stocktake');
    Route::post('/stocktake', [OutletPortalController::class, 'storeStocktake'])->name('stocktake.store');
    Route::get('/usage', [OutletPortalController::class, 'usage'])->name('usage.index');
});
