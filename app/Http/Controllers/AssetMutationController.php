<?php

namespace App\Http\Controllers;

use App\AssetMutationService;
use App\Http\Requests\StoreAssetMutationRequest;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssetMutationController extends Controller
{
    public function create(): View
    {
        return view('mutations.create', [
            'assets' => Asset::with(['category', 'location', 'room'])->orderBy('asset_number')->get(),
            'locations' => Location::orderBy('code')->get(),
            'rooms' => Room::with('location')->orderBy('code')->get(),
        ]);
    }

    public function store(StoreAssetMutationRequest $request, AssetMutationService $service): RedirectResponse
    {
        $service->move($request->user(), $request->validated());

        return redirect()->route('inventaris.index');
    }
}
