<?php

namespace App;

use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProcurementRequestService
{
    public function save(User $user, array $data, ?ProcurementRequest $proposal = null): ProcurementRequest
    {
        try {
            return DB::transaction(function () use ($user, $data, $proposal): ProcurementRequest {
                if ($proposal) {
                    $proposal = ProcurementRequest::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
                    Gate::forUser($user)->authorize('update', $proposal);
                    if ($proposal->version !== (int) $data['version']) {
                        throw ValidationException::withMessages(['version' => 'Draf telah berubah. Muat ulang sebelum menyimpan.']);
                    }
                    $proposal->version++;
                } else {
                    $existing = ProcurementRequest::where('submission_key', $data['submission_key'])->first();
                    if ($existing) {
                        abort_unless($existing->requester_id === $user->id, 404);

                        return $existing;
                    }
                    $proposal = new ProcurementRequest([
                        'submission_key' => $data['submission_key'],
                        'letter_number' => 'PGB/'.now()->format('Ymd').'/'.Str::upper(Str::random(8)),
                        'requester_id' => $user->id,
                        'requester_name' => $user->name,
                        'status' => 'draft',
                        'version' => 1,
                    ]);
                }
                $proposal->fill(array_intersect_key($data, array_flip(['division', 'subject', 'request_date', 'purpose', 'notes', 'selection_mode'])));
                $total = '0.00';
                $known = true;
                $items = [];
                foreach ($data['items'] as $item) {
                    $price = $item['estimated_unit_price'] ?? null;
                    $price = $price === '' ? null : $price;
                    $subtotal = $price === null ? null : bcmul((string) $price, (string) $item['quantity'], 2);
                    if ($subtotal === null) {
                        $known = false;
                    } else {
                        $total = bcadd($total, $subtotal, 2);
                    }
                    $items[] = array_replace(array_intersect_key($item, array_flip(['name', 'category', 'quantity', 'unit', 'specification'])), ['estimated_unit_price' => $price, 'subtotal' => $subtotal]);
                }
                $proposal->estimated_total = $proposal->selection_mode === 'all' && $known ? $total : null;
                $proposal->save();
                $proposal->items()->delete();
                $proposal->items()->createMany($items);

                return $proposal;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if ($proposal !== null) {
                throw $exception;
            }
            $existing = ProcurementRequest::where('submission_key', $data['submission_key'])->first();
            if (! $existing) {
                throw $exception;
            }
            abort_unless($existing->requester_id === $user->id, 404);

            return $existing;
        }
    }

    public function submit(User $user, ProcurementRequest $proposal): void
    {
        DB::transaction(function () use ($user, $proposal): void {
            $proposal = ProcurementRequest::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            abort_unless($proposal->requester_id === $user->id, 403);
            if ($proposal->status === 'pending_finance') {
                return;
            }
            if ($proposal->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Pengajuan sudah diproses dan tidak dapat dikirim ulang.']);
            }
            if (! $proposal->items()->exists()) {
                throw ValidationException::withMessages(['items' => 'Isi minimal satu barang sebelum mengirim.']);
            }
            $proposal->update(['status' => 'pending_finance', 'submitted_at' => now()]);
        });
    }

    public function review(User $user, ProcurementRequest $proposal, string $level, array $data): void
    {
        DB::transaction(function () use ($user, $proposal, $level, $data): void {
            $proposal = ProcurementRequest::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize($level === 'finance' ? 'reviewFinance' : 'reviewDirector', $proposal);
            $selected = null;
            if ($level === 'finance' && $data['decision'] === 'approved' && $proposal->selection_mode === 'alternatives') {
                $selected = $proposal->items()->find($data['selected_item_id'] ?? null);
                if (! $selected) {
                    throw ValidationException::withMessages(['selected_item_id' => 'Pilih satu alternatif dari pengajuan ini untuk direkomendasikan ke Direksi.']);
                }
            } elseif (! empty($data['selected_item_id'])) {
                throw ValidationException::withMessages(['selected_item_id' => 'Pilihan alternatif hanya ditetapkan Keuangan pada pengajuan alternatif.']);
            }
            $proposal->decisions()->create([
                'reviewer_id' => $user->id, 'reviewer_name' => $user->name,
                'level' => $level, 'decision' => $data['decision'], 'version' => $proposal->version,
                'notes' => $data['notes'] ?? null, 'selected_item_id' => $selected?->id, 'decided_at' => now(),
            ]);
            $proposal->update(['status' => $data['decision'] === 'rejected' ? 'rejected' : ($level === 'finance' ? 'pending_director' : 'approved')]);
        });
    }
}
