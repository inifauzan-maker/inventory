<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceiptWorkflowRequest;
use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Models\AssetCategory;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\ReceiptWorkflowService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GoodsReceiptController extends Controller
{
    public function create(): View
    {
        return view('receipts.create', [
            'purchaseOrders' => PurchaseOrder::with('items')->where('status', 'approved')->orderBy('order_number')->get(),
            'categories' => AssetCategory::orderBy('code')->get(),
            'locations' => Location::orderBy('code')->get(),
        ]);
    }

    public function show(GoodsReceipt $goodsReceipt): View
    {
        $goodsReceipt->load([
            'purchaseOrder',
            'items' => fn ($query) => $query->with(['purchaseOrderItem', 'category', 'location', 'assets']),
        ]);

        return view('receipts.show', [
            'receipt' => $goodsReceipt,
            'categories' => AssetCategory::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    public function store(StoreGoodsReceiptRequest $request, ReceiptWorkflowService $service): RedirectResponse
    {
        $receipt = $service->receive($request->validated());

        return redirect()->route('penerimaan.show', $receipt)->with('success', 'Barang diterima. Lanjutkan pemeriksaan barang dan verifikasi dokumen sebelum membuat inventaris.');
    }

    public function advance(ReceiptWorkflowRequest $request, GoodsReceipt $goodsReceipt, string $step, ReceiptWorkflowService $service): RedirectResponse
    {
        try {
            $service->advance($goodsReceipt, $request->user(), $step, $request->validated());
        } catch (QueryException $exception) {
            report($exception);
            throw ValidationException::withMessages(['stage' => 'Pencatatan gagal. Tidak ada perubahan tahap, aset, atau nomor yang disimpan. Periksa data lalu coba lagi.']);
        }

        return redirect()->route('penerimaan.show', $goodsReceipt)->with('success', 'Tahap penerimaan tersimpan.');
    }
}
