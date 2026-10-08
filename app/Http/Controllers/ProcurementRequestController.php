<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewProcurementRequest;
use App\Http\Requests\StoreProcurementRequest;
use App\Models\ProcurementRequest;
use App\ProcurementLetterWriter;
use App\ProcurementRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProcurementRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = ProcurementRequest::visibleTo($request->user());
        $status = $request->query('status');
        if (array_key_exists((string) $status, ProcurementRequest::statusLabels())) {
            $query->where('status', $status);
        }

        return view('procurement-requests.index', ['proposals' => $query->with('requester')->latest('id')->paginate(20)->withQueryString(), 'status' => $status]);
    }

    public function create(): View
    {
        return view('procurement-requests.form', ['proposal' => new ProcurementRequest, 'submissionKey' => (string) Str::uuid()]);
    }

    public function store(StoreProcurementRequest $request, ProcurementRequestService $service): RedirectResponse
    {
        $proposal = $service->save($request->user(), $request->validated());

        return redirect()->route('pengajuan.show', $proposal)->with('success', 'Draf tersimpan. Periksa surat, lalu kirim ke Keuangan.');
    }

    public function show(ProcurementRequest $procurementRequest): View
    {
        Gate::authorize('view', $procurementRequest);
        $procurementRequest->load(['items', 'decisions.selectedItem', 'purchaseOrders']);

        return view('procurement-requests.show', ['proposal' => $procurementRequest]);
    }

    public function edit(ProcurementRequest $procurementRequest): View
    {
        Gate::authorize('update', $procurementRequest);
        $procurementRequest->load('items');

        return view('procurement-requests.form', ['proposal' => $procurementRequest, 'submissionKey' => $procurementRequest->submission_key]);
    }

    public function update(StoreProcurementRequest $request, ProcurementRequest $procurementRequest, ProcurementRequestService $service): RedirectResponse
    {
        $service->save($request->user(), $request->validated(), $procurementRequest);

        return redirect()->route('pengajuan.show', $procurementRequest)->with('success', 'Draf diperbarui.');
    }

    public function submit(Request $request, ProcurementRequest $procurementRequest, ProcurementRequestService $service): RedirectResponse
    {
        $service->submit($request->user(), $procurementRequest);

        return redirect()->route('pengajuan.show', $procurementRequest)->with('success', 'Pengajuan masuk ke antrean Keuangan.');
    }

    public function review(ReviewProcurementRequest $request, ProcurementRequest $procurementRequest, string $level, ProcurementRequestService $service): RedirectResponse
    {
        $service->review($request->user(), $procurementRequest, $level, $request->validated());

        return redirect()->route('pengajuan.show', $procurementRequest)->with('success', 'Keputusan tersimpan'.($level === 'finance' && $request->validated('decision') === 'approved' ? ' dan pengajuan diteruskan ke Direksi.' : '.'));
    }

    public function letter(ProcurementRequest $procurementRequest): View
    {
        Gate::authorize('view', $procurementRequest);
        $procurementRequest->load(['items', 'decisions.selectedItem']);

        return view('procurement-requests.letter', ['proposal' => $procurementRequest]);
    }

    public function document(ProcurementRequest $procurementRequest, ProcurementLetterWriter $writer): BinaryFileResponse
    {
        Gate::authorize('view', $procurementRequest);
        $procurementRequest->load(['items', 'decisions.selectedItem']);
        $path = $writer->write($procurementRequest);

        return response()->download($path, 'Surat-Pengajuan-'.$procurementRequest->id.'.docx')->deleteFileAfterSend(true);
    }
}
