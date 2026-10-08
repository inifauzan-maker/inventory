<?php

namespace App\Http\Controllers;

use App\AssetActivationService;
use App\AssetHandoverDocument;
use App\AssetHandoverWriter;
use App\Http\Requests\AssetActivationRequest;
use App\InventoryQrCode;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetActivationController extends Controller
{
    public function advance(AssetActivationRequest $request, Asset $asset, string $step, AssetActivationService $service): RedirectResponse
    {
        $service->advance($asset, $request->user(), $step, $request->validated());

        return redirect()->route('aset.show', $asset)->with('success', 'Tahap aset tersimpan.');
    }

    public function label(Asset $asset): View
    {
        $asset->load(['catalogItem', 'category', 'location']);

        return view('assets.label', ['asset' => $asset, 'qrCode' => (new InventoryQrCode)->dataUri($asset->asset_number)]);
    }

    public function handoverLetter(Asset $asset, AssetHandoverDocument $document): View
    {
        $this->ensureHandoverAccess();

        return view('assets.handover-letter', ['asset' => $asset, 'document' => $document->data($asset)]);
    }

    public function handoverWord(Asset $asset, AssetHandoverDocument $document, AssetHandoverWriter $writer): BinaryFileResponse
    {
        $this->ensureHandoverAccess();
        $path = $writer->write($document->data($asset));

        return response()->download($path, 'BAST-Aset-'.$asset->id.'.docx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->deleteFileAfterSend(true);
    }

    private function ensureHandoverAccess(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL, User::ROLE_DIREKSI, User::ROLE_KEUANGAN]), 403);
    }
}
