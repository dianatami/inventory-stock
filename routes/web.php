<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\StockMutationController;
use App\Http\Controllers\StockReportController;
use App\Http\Controllers\UserManagementController;

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
    return view('dashboard');
});


Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.process');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'role:Manager'])->group(function () {
    Route::get('/akses-manager', function () {
        return 'Halaman khusus Manager';
    });
});

Route::middleware(['auth', 'role:Admin Prolog'])->group(function () {
    Route::get('/akses-admin-prolog', function () {
        return 'Halaman khusus Admin Prolog';
    });
});

Route::middleware(['auth', 'role:Kepala Logistik'])->group(function () {
    Route::get('/akses-kepala-logistik', function () {
        return 'Halaman khusus Kepala Logistik';
    });
});

Route::middleware(['auth', 'role:Manager,Admin Prolog'])
    ->group(function () {
        Route::resource('categories', CategoryController::class)
            ->except(['show']);
    });

Route::middleware(['auth', 'role:Manager,Admin Prolog'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('products', ProductController::class);
});


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

Route::get('/stock-mutations/create', [StockMutationController::class, 'create'])
    ->name('stock-mutations.create');

Route::post('/stock-mutations', [StockMutationController::class, 'store'])
    ->name('stock-mutations.store');

    
Route::get('/reports/stock', [StockReportController::class, 'index'])
    ->name('reports.stock');

    
Route::middleware(['auth', 'role:Manager'])->group(function () {
    Route::resource('users', UserManagementController::class)
        ->except(['show']);

    Route::patch(
        '/users/{user}/toggle-status',
        [UserManagementController::class, 'toggleStatus']
    )->name('users.toggle-status');
});
