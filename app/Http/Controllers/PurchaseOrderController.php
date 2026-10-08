<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\ProcurementRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\PurchaseOrderLetterWriter;
use App\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $proposal = null;
        if ($request->filled('proposal')) {
            abort_unless($request->user()->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]), 403);
            $proposal = ProcurementRequest::where('status', 'approved')->findOrFail($request->integer('proposal'));
        }

        $sourceItems = $proposal?->orderableItems() ?? collect();
        foreach ($sourceItems as $item) {
            $ordered = PurchaseOrderItem::where('procurement_request_item_id', $item->id)->whereHas('purchaseOrder', fn ($query) => $query->where('status', '!=', 'rejected'))->sum('quantity');
            $item->remaining_quantity = max(0, $item->quantity - $ordered);
        }

        return view('procurement.index', [
            'sourceProposal' => $proposal,
            'sourceItems' => $sourceItems,
            'approvedProposals' => $request->user()->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]) ? ProcurementRequest::where('status', 'approved')->orderByDesc('id')->get() : collect(),
            'purchaseOrders' => PurchaseOrder::with('items')->latest()->get(),
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['procurementRequest', 'items.procurementRequestItem', 'receipts.items.purchaseOrderItem']);

        return view('procurement.show', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function letter(PurchaseOrder $purchaseOrder): View
    {
        $this->ensureLetterAccess();
        $purchaseOrder->load(['procurementRequest', 'items.procurementRequestItem']);

        return view('procurement.letter', ['order' => $purchaseOrder]);
    }

    public function document(PurchaseOrder $purchaseOrder, PurchaseOrderLetterWriter $writer): BinaryFileResponse
    {
        $this->ensureLetterAccess();
        $purchaseOrder->load(['procurementRequest', 'items.procurementRequestItem']);
        $path = $writer->write($purchaseOrder);

        return response()->download($path, 'Surat-PO-'.$purchaseOrder->id.'.docx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->deleteFileAfterSend(true);
    }

    private function ensureLetterAccess(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL, User::ROLE_DIREKSI, User::ROLE_KEUANGAN]), 403);
    }

    public function store(StorePurchaseOrderRequest $request, PurchaseOrderService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()->route('pengadaan.index');
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->ensureApprovalAccess();
        $this->changeStatus($purchaseOrder, 'approved');

        return redirect()->route('pengadaan.index');
    }

    public function reject(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->ensureApprovalAccess();
        $this->changeStatus($purchaseOrder, 'rejected');

        return redirect()->route('pengadaan.index');
    }

    private function changeStatus(PurchaseOrder $order, string $status): void
    {
        DB::transaction(function () use ($order, $status): void {
            if ($order->procurement_request_id) {
                ProcurementRequest::whereKey($order->procurement_request_id)->lockForUpdate()->firstOrFail();
            }
            $order = PurchaseOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Hanya PO draf yang dapat disetujui atau ditolak.']);
            }
            $order->update(['status' => $status]);
        });
    }

    protected function ensureApprovalAccess(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_DIREKSI])) {
            abort(403);
        }
    }
}
