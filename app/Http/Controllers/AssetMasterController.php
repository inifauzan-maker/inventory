<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCatalogItemRequest;
use App\InventoryNumberService;
use App\Models\AssetCategory;
use App\Models\CatalogItem;
use App\Models\InventoryNumberSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetMasterController extends Controller
{
    public function items(): View
    {
        return view('master.items', ['items' => CatalogItem::with('category')->orderBy('code')->paginate(20), 'categories' => AssetCategory::orderBy('name')->get()]);
    }

    public function storeItem(StoreCatalogItemRequest $request, InventoryNumberService $numbers): RedirectResponse
    {
        DB::transaction(function () use ($request, $numbers): void {
            $data = $request->validated();
            $data['code'] = $data['code'] ?? $numbers->nextItemCode();
            CatalogItem::create($data);
        });

        return redirect()->route('master.barang.index')->with('success', 'Master barang tersimpan.');
    }

    public function numbering(): View
    {
        return view('master.numbering', ['settings' => InventoryNumberSetting::findOrFail(1)]);
    }

    public function saveNumbering(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'], 'include_item_code' => ['required', 'boolean']], ['prefix.regex' => 'Awalan hanya boleh huruf, angka, garis bawah, dan tanda hubung.']);
        DB::transaction(function () use ($data): void {
            InventoryNumberSetting::whereKey(1)->lockForUpdate()->firstOrFail()->update($data);
        });

        return redirect()->route('master.nomor.index')->with('success', 'Format nomor inventaris diperbarui untuk aset baru.');
    }
}
