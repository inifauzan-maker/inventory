<?php

use App\Http\Controllers\AssetActivationController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetImportController;
use App\Http\Controllers\AssetMasterController;
use App\Http\Controllers\AssetMutationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProcurementRequestController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/pengajuan', [ProcurementRequestController::class, 'index'])->name('pengajuan.index');
    Route::get('/pengajuan/create', [ProcurementRequestController::class, 'create'])->name('pengajuan.create');
    Route::post('/pengajuan', [ProcurementRequestController::class, 'store'])->name('pengajuan.store');
    Route::get('/pengajuan/{procurementRequest}', [ProcurementRequestController::class, 'show'])->name('pengajuan.show');
    Route::get('/pengajuan/{procurementRequest}/edit', [ProcurementRequestController::class, 'edit'])->name('pengajuan.edit');
    Route::put('/pengajuan/{procurementRequest}', [ProcurementRequestController::class, 'update'])->name('pengajuan.update');
    Route::post('/pengajuan/{procurementRequest}/kirim', [ProcurementRequestController::class, 'submit'])->name('pengajuan.submit');
    Route::post('/pengajuan/{procurementRequest}/review/{level}', [ProcurementRequestController::class, 'review'])->whereIn('level', ['finance', 'director'])->name('pengajuan.review');
    Route::get('/pengajuan/{procurementRequest}/surat', [ProcurementRequestController::class, 'letter'])->name('pengajuan.letter');
    Route::get('/pengajuan/{procurementRequest}/docx', [ProcurementRequestController::class, 'document'])->name('pengajuan.document');
    Route::get('/inventaris', [InventoryController::class, 'index'])->name('inventaris.index');
    Route::get('/audit', [InventoryController::class, 'audit'])->name('audit.index');
    Route::get('/laporan', [InventoryController::class, 'report'])->name('laporan.index');
    Route::get('/laporan/export', [InventoryController::class, 'export'])->name('laporan.export');
    Route::get('/laporan/export-excel', [InventoryController::class, 'exportExcel'])->name('laporan.export.excel');
    Route::get('/aset/create', [AssetController::class, 'create'])->name('aset.create');
    Route::middleware('role:superadmin,operasional')->prefix('impor-aset')->name('aset.import.')->group(function () {
        Route::get('/', [AssetImportController::class, 'index'])->name('index');
        Route::get('/template', [AssetImportController::class, 'template'])->name('template');
        Route::get('/contoh', [AssetImportController::class, 'example'])->name('example');
        Route::post('/preview', [AssetImportController::class, 'preview'])->middleware('throttle:10,1')->name('preview');
        Route::post('/konfirmasi', [AssetImportController::class, 'confirm'])->name('confirm');
        Route::post('/pemetaan', [AssetImportController::class, 'mapping'])->name('mapping');
    });
    Route::get('/aset/{asset}', [AssetController::class, 'show'])->name('aset.show');
    Route::get('/aset/{asset}/bast', [AssetActivationController::class, 'handoverLetter'])->name('aset.bast');
    Route::get('/aset/{asset}/bast/docx', [AssetActivationController::class, 'handoverWord'])->name('aset.bast.docx');
    Route::get('/aset/{asset}/label', [AssetActivationController::class, 'label'])->name('aset.label');
    Route::post('/aset/{asset}/tahap/{step}', [AssetActivationController::class, 'advance'])->whereIn('step', ['print', 'attach', 'place', 'handover'])->name('aset.activation');
    Route::post('/aset', [AssetController::class, 'store'])->name('aset.store');
    Route::get('/aset/{asset}/edit', [AssetController::class, 'edit'])->name('aset.edit');
    Route::put('/aset/{asset}', [AssetController::class, 'update'])->name('aset.update');
    Route::get('/mutasi', [AssetMutationController::class, 'create'])->name('mutasi.create');
    Route::post('/mutasi', [AssetMutationController::class, 'store'])->name('mutasi.store');
    Route::get('/pengadaan', [PurchaseOrderController::class, 'index'])->name('pengadaan.index');
    Route::get('/pengadaan/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('pengadaan.show');
    Route::get('/pengadaan/{purchaseOrder}/surat', [PurchaseOrderController::class, 'letter'])->name('pengadaan.letter');
    Route::get('/pengadaan/{purchaseOrder}/docx', [PurchaseOrderController::class, 'document'])->name('pengadaan.document');
    Route::post('/pengadaan', [PurchaseOrderController::class, 'store'])->name('pengadaan.store');
    Route::post('/pengadaan/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('pengadaan.approve');
    Route::post('/pengadaan/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('pengadaan.reject');
    Route::get('/penerimaan', [GoodsReceiptController::class, 'create'])->name('penerimaan.create');
    Route::get('/penerimaan/{goodsReceipt}', [GoodsReceiptController::class, 'show'])->name('penerimaan.show');
    Route::post('/penerimaan', [GoodsReceiptController::class, 'store'])->name('penerimaan.store');
    Route::post('/penerimaan/{goodsReceipt}/tahap/{step}', [GoodsReceiptController::class, 'advance'])->whereIn('step', ['inspect', 'documents', 'register'])->name('penerimaan.workflow');

    Route::prefix('master')->group(function () {
        Route::middleware('role:superadmin,operasional,kacab,direksi')->group(function () {
            Route::get('/barang', [AssetMasterController::class, 'items'])->name('master.barang.index');
            Route::get('/nomor-inventaris', [AssetMasterController::class, 'numbering'])->name('master.nomor.index');
        });
        Route::post('/barang', [AssetMasterController::class, 'storeItem'])->middleware('role:superadmin,operasional')->name('master.barang.store');
        Route::post('/nomor-inventaris', [AssetMasterController::class, 'saveNumbering'])->middleware('role:superadmin')->name('master.nomor.store');
        Route::get('/kategori', [AssetCategoryController::class, 'index'])->name('master.kategori.index');
        Route::post('/kategori', [AssetCategoryController::class, 'store'])->name('master.kategori.store');

        Route::get('/lokasi', [LocationController::class, 'index'])->name('master.lokasi.index');
        Route::post('/lokasi', [LocationController::class, 'store'])->name('master.lokasi.store');

        Route::get('/ruangan', [RoomController::class, 'index'])->name('master.ruangan.index');
        Route::post('/ruangan', [RoomController::class, 'store'])->name('master.ruangan.store');
    });
});
