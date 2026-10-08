<?php

namespace App\Http\Controllers;

use App\AssetImportService;
use App\Http\Requests\ConfirmAssetImportRequest;
use App\Http\Requests\PreviewAssetImportRequest;
use App\InventoryWorkbookReader;
use App\InventoryWorkbookWriter;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetImportController extends Controller
{
    public function index(Request $request, AssetImportService $service): View
    {
        $token = $request->session()->get('asset_import_token');
        $payload = $token ? Cache::get('asset-import:'.$token) : null;
        if ($payload && $payload['user_id'] !== $request->user()->id) {
            $payload = null;
        }

        $mapping = $request->old('mapping', $request->session()->get('asset_import_mapping', []));
        $mapping = is_array($mapping) ? array_map(fn ($value) => is_scalar($value) || $value === null ? $value : null, $mapping) : [];

        return view('assets.import', [
            'token' => $payload ? $token : null,
            'filename' => $payload['filename'] ?? null,
            'preview' => $payload ? $service->preview($payload['rows'], $mapping) : null,
        ]);
    }

    public function template(): BinaryFileResponse
    {
        return response()->download(base_path('docs/Daftar_Inventaris_Data_Awal.xlsx'), 'Daftar_Inventaris_Data_Awal.xlsx');
    }

    public function example(InventoryWorkbookWriter $writer): BinaryFileResponse
    {
        $path = $writer->write($writer->exampleRows());

        return response()->download($path, 'Contoh_Import_Inventaris.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function preview(PreviewAssetImportRequest $request, InventoryWorkbookReader $reader): RedirectResponse
    {
        $rows = $reader->read($request->file('file')->getRealPath());
        $previous = $request->session()->get('asset_import_token');
        if ($previous) {
            Cache::forget('asset-import:'.$previous);
        }
        $token = (string) Str::uuid();
        Cache::put('asset-import:'.$token, [
            'user_id' => $request->user()->id,
            'filename' => $request->file('file')->getClientOriginalName(),
            'rows' => $rows,
        ], now()->addMinutes(30));
        $request->session()->put('asset_import_token', $token);
        $request->session()->forget('asset_import_mapping');

        return redirect()->route('aset.import.index');
    }

    public function confirm(ConfirmAssetImportRequest $request, AssetImportService $service): RedirectResponse
    {
        $token = $request->validated('token');
        try {
            $count = Cache::lock('asset-import-lock:'.$token, 120)->block(3, function () use ($request, $service, $token): int {
                $payload = Cache::get('asset-import:'.$token);
                if (! $payload || $payload['user_id'] !== $request->user()->id || $request->session()->get('asset_import_token') !== $token) {
                    throw ValidationException::withMessages(['import' => 'Preview kedaluwarsa atau sudah digunakan. Unggah ulang file.']);
                }
                $count = $service->confirm($payload['rows'], $request->validated('mapping', []));
                Cache::forget('asset-import:'.$token);
                $request->session()->forget('asset_import_token');
                $request->session()->forget('asset_import_mapping');

                return $count;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['import' => 'Nomor inventaris sudah digunakan saat konfirmasi. Tidak ada aset yang disimpan. Periksa preview kembali.']);
        } catch (QueryException $exception) {
            report($exception);
            throw ValidationException::withMessages(['import' => 'Penyimpanan impor gagal. Tidak ada aset yang disimpan. Periksa data atau coba lagi.']);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['import' => 'Impor sedang diproses. Tunggu lalu periksa hasilnya.']);
        }

        return redirect()->route('aset.import.index')->with('success', $count.' aset berhasil diimpor. Seluruh baris disimpan.');
    }

    public function mapping(ConfirmAssetImportRequest $request): RedirectResponse
    {
        $token = $request->validated('token');
        $payload = Cache::get('asset-import:'.$token);
        if (! $payload || $payload['user_id'] !== $request->user()->id || $request->session()->get('asset_import_token') !== $token) {
            throw ValidationException::withMessages(['import' => 'Preview kedaluwarsa atau sudah digunakan. Unggah ulang file.']);
        }

        $request->session()->put('asset_import_mapping', $request->validated('mapping', []));

        return redirect()->route('aset.import.index');
    }
}
