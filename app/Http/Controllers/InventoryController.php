<?php

namespace App\Http\Controllers;

use App\InventoryWorkbookWriter;
use App\Models\Asset;
use App\Models\AssetActivity;
use App\Models\AssetCategory;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $assetsQuery = $this->filteredAssets($request);

        $assets = $assetsQuery->get();

        $allAssets = Asset::with(['category', 'location', 'room', 'catalogItem'])
            ->get();

        $summary = [
            'total' => $allAssets->count(),
            'baik' => $allAssets->where('condition', 'baik')->count(),
            'rusak' => $allAssets->where('condition', 'rusak')->count(),
            'dipakai' => $allAssets->where('activation_stage', 'active')->where('usage_status', 'dipakai')->count(),
            'cadangan' => $allAssets->where('usage_status', 'cadangan')->count(),
        ];

        $categories = AssetCategory::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $categorySummary = AssetCategory::withCount('assets')->orderBy('name')->get();
        $locationSummary = Location::withCount('assets')->orderBy('name')->get();

        return view('inventory.index', [
            'assets' => $assets,
            'summary' => $summary,
            'categories' => $categories,
            'locations' => $locations,
            'categorySummary' => $categorySummary,
            'locationSummary' => $locationSummary,
            'filters' => $request->only(['search', 'category_id', 'location_id', 'condition', 'usage_status']),
        ]);
    }

    public function report(): View
    {
        $assets = Asset::with(['category', 'location', 'room', 'catalogItem'])->orderBy('asset_number')->get();

        $categorySummary = AssetCategory::withCount('assets')->orderBy('name')->get();
        $locationSummary = Location::withCount('assets')->orderBy('name')->get();

        return view('inventory.report', [
            'assets' => $assets,
            'categorySummary' => $categorySummary,
            'locationSummary' => $locationSummary,
            'summary' => [
                'total' => $assets->count(),
                'baik' => $assets->where('condition', 'baik')->count(),
                'rusak' => $assets->where('condition', 'rusak')->count(),
                'dipakai' => $assets->where('activation_stage', 'active')->where('usage_status', 'dipakai')->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $assets = $this->filteredAssets($request)->lazy(200);

        return response()->streamDownload(function () use ($assets) {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Nomor Aset',
                'Nama',
                'Kategori',
                'Lokasi',
                'Ruangan',
                'Kondisi',
                'Status',
                'Serial Number',
                'Harga Beli',
                'Tanggal Perolehan',
                'Deskripsi',
            ]);

            foreach ($assets as $asset) {
                $values = [
                    $asset->asset_number,
                    $asset->name,
                    $asset->category?->name ?? '-',
                    $asset->location?->name ?? '-',
                    $asset->room?->name ?? '-',
                    $asset->condition,
                    $asset->usage_status,
                    $asset->serial_number ?? '-',
                    $asset->purchase_price !== null ? number_format((float) $asset->purchase_price, 0, ',', '.') : '-',
                    $asset->acquired_at?->format('d-m-Y') ?? '-',
                    $asset->description ?? '-',
                ];
                fputcsv($handle, array_map(fn ($value) => is_string($value) && preg_match('/^[\s\x00-\x1F]*[=+@-]/u', $value) ? "'".$value : $value, $values));
            }

            fclose($handle);
        }, 'laporan-inventaris.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function audit(): View
    {
        $activities = AssetActivity::with(['asset', 'user'])
            ->orderByDesc('occurred_at')
            ->get();

        return view('audit.index', [
            'activities' => $activities,
        ]);
    }

    public function exportExcel(Request $request, InventoryWorkbookWriter $writer): BinaryFileResponse
    {
        $rows = $this->filteredAssets($request)->lazy(200)->map(fn (Asset $asset): array => $writer->assetRow($asset));
        $path = $writer->write($rows);

        return response()->download($path, 'inventaris.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    private function filteredAssets(Request $request): Builder
    {
        return Asset::with(['category', 'location', 'room', 'catalogItem'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim((string) $request->input('search'));
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('asset_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn (Builder $query) => $query->where('asset_category_id', $request->input('category_id')))
            ->when($request->filled('location_id'), fn (Builder $query) => $query->where('location_id', $request->input('location_id')))
            ->when($request->filled('condition'), fn (Builder $query) => $query->where('condition', $request->input('condition')))
            ->when($request->filled('usage_status'), fn (Builder $query) => $query->where('usage_status', $request->input('usage_status')))
            ->orderBy('asset_number');
    }
}
