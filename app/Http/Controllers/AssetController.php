<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\CatalogItem;
use App\Models\Location;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function create(): View
    {
        return view('assets.create', [
            'catalogItems' => CatalogItem::orderBy('code')->get(),
            'categories' => AssetCategory::orderBy('code')->get(),
            'locations' => Location::orderBy('code')->get(),
            'rooms' => Room::with('location')->orderBy('code')->get(),
        ]);
    }

    public function show(Asset $asset): View
    {
        $asset->load([
            'catalogItem',
            'category',
            'location',
            'room',
            'mutations' => fn ($query) => $query->with(['fromLocation', 'toLocation', 'fromRoom', 'toRoom', 'movedBy'])->orderByDesc('moved_at'),
            'activities' => fn ($query) => $query->with('user')->orderByDesc('occurred_at'),
        ]);

        return view('assets.show', [
            'asset' => $asset,
            'locations' => Location::orderBy('name')->get(),
            'rooms' => Room::with('location')->orderBy('name')->get(),
        ]);
    }

    public function edit(Asset $asset): View
    {
        return view('assets.edit', [
            'asset' => $asset,
            'catalogItems' => CatalogItem::orderBy('code')->get(),
            'categories' => AssetCategory::orderBy('code')->get(),
            'locations' => Location::orderBy('code')->get(),
            'rooms' => Room::with('location')->orderBy('code')->get(),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(fn () => Asset::create(array_replace($validated, ['activation_stage' => 'registered', 'verification_confirmed_at' => now(), 'verification_confirmed_by' => $request->user()->id])));

        return redirect()->route('inventaris.index');
    }

    public function update(StoreAssetRequest $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validated();

        $validated['asset_number'] = $asset->asset_number;
        DB::transaction(fn () => $asset->update($validated));

        return redirect()->route('inventaris.index');
    }
}
