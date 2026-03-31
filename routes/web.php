<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\DrugUnitController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StockRequestController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

// Legal pages (public, no auth required)
Route::get('/cgu', fn () => inertia('Legal/CGU'))->name('legal.cgu');
Route::get('/confidentialite', fn () => inertia('Legal/Confidentialite'))->name('legal.confidentialite');
Route::get('/cookies', fn () => inertia('Legal/Cookies'))->name('legal.cookies');
Route::get('/mentions-legales', fn () => inertia('Legal/MentionsLegales'))->name('legal.mentions');
Route::get('/contact', fn () => inertia('Legal/Contact'))->name('legal.contact');
Route::get('/a-propos', fn () => inertia('Legal/APropos'))->name('legal.about');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Drug Management
    Route::get('drugs', [DrugController::class, 'index'])->middleware('permission:view_drug')->name('drugs.index');
    Route::get('drugs/create', [DrugController::class, 'create'])->middleware('permission:create_drug')->name('drugs.create');
    Route::post('drugs', [DrugController::class, 'store'])->middleware('permission:create_drug')->name('drugs.store');
    Route::get('drugs/{drug}/edit', [DrugController::class, 'edit'])->middleware('permission:edit_drug')->name('drugs.edit');
    Route::put('drugs/{drug}', [DrugController::class, 'update'])->middleware('permission:edit_drug')->name('drugs.update');
    Route::delete('drugs/{drug}', [DrugController::class, 'destroy'])->middleware('permission:delete_drug')->name('drugs.destroy');

    // Stock / Inventory
    Route::get('drug-units', [DrugUnitController::class, 'index'])->middleware('permission:view_stock')->name('drug-units.index');
    Route::get('drug-units/create', [DrugUnitController::class, 'create'])->middleware('role:super_admin|pharmacy_admin|pharmacy_staff')->name('drug-units.create');
    Route::post('drug-units', [DrugUnitController::class, 'store'])->middleware('role:super_admin|pharmacy_admin|pharmacy_staff')->name('drug-units.store');

    // Transfers
    Route::get('transfers', [TransferController::class, 'index'])->middleware('permission:transfer_stock')->name('transfers.index');
    Route::get('transfers/create', [TransferController::class, 'create'])->middleware('permission:transfer_stock')->name('transfers.create');
    Route::get('transfers/lookup', [TransferController::class, 'lookup'])->middleware('permission:transfer_stock')->name('transfers.lookup');
    Route::post('transfers', [TransferController::class, 'store'])->middleware('permission:transfer_stock')->name('transfers.store');
    Route::get('transfers/{transfer}', [TransferController::class, 'show'])->middleware('permission:transfer_stock')->name('transfers.show');
    Route::get('transfers/{transfer}/print', [TransferController::class, 'print'])->middleware('permission:transfer_stock')->name('transfers.print');

    // Sales
    Route::get('sales', [SaleController::class, 'index'])->middleware('permission:sell_unit')->name('sales.index');
    Route::get('sales/create', [SaleController::class, 'create'])->middleware('permission:sell_unit')->name('sales.create');
    Route::get('sales/scan', [SaleController::class, 'scan'])->middleware('permission:sell_unit')->name('sales.scan');
    Route::post('sales', [SaleController::class, 'store'])->middleware('permission:sell_unit')->name('sales.store');

    // Administration
    Route::get('roles', [RoleController::class, 'index'])->middleware('permission:manage_roles')->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])->middleware('permission:manage_roles')->name('roles.create');
    Route::post('roles', [RoleController::class, 'store'])->middleware('permission:manage_roles')->name('roles.store');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:manage_roles')->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:manage_roles')->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:manage_roles')->name('roles.destroy');

    Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:manage_roles')->name('permissions.index');
    Route::get('permissions/create', [PermissionController::class, 'create'])->middleware('permission:manage_roles')->name('permissions.create');
    Route::post('permissions', [PermissionController::class, 'store'])->middleware('permission:manage_roles')->name('permissions.store');
    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:manage_roles')->name('permissions.destroy');

    Route::get('users', [UserController::class, 'index'])->middleware('permission:manage_users')->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->middleware('permission:manage_users')->name('users.create');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:manage_users')->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:manage_users')->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:manage_users')->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:manage_users')->name('users.destroy');

    Route::get('categories', [CategoryController::class, 'index'])->middleware('permission:view_category')->name('categories.index');
    Route::get('categories/create', [CategoryController::class, 'create'])->middleware('permission:create_category')->name('categories.create');
    Route::post('categories', [CategoryController::class, 'store'])->middleware('permission:create_category')->name('categories.store');

    Route::get('depots', [DepotController::class, 'index'])->middleware('permission:create_depot')->name('depots.index');
    Route::get('depots/create', [DepotController::class, 'create'])->middleware('permission:create_depot')->name('depots.create');
    Route::post('depots', [DepotController::class, 'store'])->middleware('permission:create_depot')->name('depots.store');
    Route::get('depots/{depot}', [DepotController::class, 'show'])->middleware('permission:create_depot')->name('depots.show');
    Route::get('depots/{depot}/edit', [DepotController::class, 'edit'])->middleware('permission:create_depot')->name('depots.edit');
    Route::put('depots/{depot}', [DepotController::class, 'update'])->middleware('permission:create_depot')->name('depots.update');
    Route::delete('depots/{depot}', [DepotController::class, 'destroy'])->middleware('permission:create_depot')->name('depots.destroy');
    Route::post('depots/{depot}/low-stock-notify', [DepotController::class, 'notifyLowStock'])->middleware('role:depot_staff')->name('depots.low-stock-notify');
    Route::post('depots/{depot}/toggle-receipts', [DepotController::class, 'toggleReceipts'])->middleware('role:super_admin|pharmacy_admin')->name('depots.toggle-receipts');
    Route::post('depots/{depot}/toggle-stats', [DepotController::class, 'toggleStats'])->middleware('role:super_admin|pharmacy_admin')->name('depots.toggle-stats');

    // Stock Requests
    Route::get('stock-requests', [StockRequestController::class, 'index'])->middleware('permission:view_stock')->name('stock-requests.index');
    Route::get('stock-requests/create', [StockRequestController::class, 'create'])->middleware('permission:view_stock')->name('stock-requests.create');
    Route::post('stock-requests', [StockRequestController::class, 'store'])->middleware('permission:view_stock')->name('stock-requests.store');
    Route::get('stock-requests/{stock_request}', [StockRequestController::class, 'show'])->middleware('permission:view_stock')->name('stock-requests.show');
    Route::put('stock-requests/{stock_request}', [StockRequestController::class, 'update'])->middleware('permission:view_stock')->name('stock-requests.update');
    Route::delete('stock-requests/{stock_request}', [StockRequestController::class, 'destroy'])->middleware('permission:view_stock')->name('stock-requests.destroy');

    // Pharmacy Settings
    Route::get('pharmacy/edit', [PharmacyController::class, 'edit'])->name('pharmacy.edit');
    Route::patch('pharmacy/update', [PharmacyController::class, 'update'])->middleware('role:super_admin|pharmacy_admin')->name('pharmacy.update');
    Route::post('pharmacy/join', [PharmacyController::class, 'join'])->name('pharmacy.join');
    Route::post('pharmacy/store', [PharmacyController::class, 'store'])->middleware('role:super_admin')->name('pharmacy.store');
    Route::delete('pharmacy/{pharmacy}', [PharmacyController::class, 'destroy'])->middleware('role:super_admin|pharmacy_admin')->name('pharmacy.destroy');
    Route::get('pharmacy/{pharmacy}/backup', [PharmacyController::class, 'backup'])->middleware('role:super_admin|pharmacy_admin')->name('pharmacy.backup');
    Route::post('pharmacy/restore', [PharmacyController::class, 'restore'])->middleware('role:super_admin')->name('pharmacy.restore');

    // Statistics
    Route::get('stats', [StatsController::class, 'index'])->middleware('permission:view_reports')->name('stats.index');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('notifications/{notificationId}/go', [NotificationController::class, 'go'])->name('notifications.go');

    // Profile Routes (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/switch-pharmacy', [ProfileController::class, 'switchPharmacy'])->name('profile.switch-pharmacy');
});

require __DIR__.'/auth.php';
