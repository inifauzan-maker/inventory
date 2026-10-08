<?php

namespace App;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetActivationService
{
    public const STAGES = ['registered' => 'Nomor diterbitkan — menunggu cetak label', 'label_printed' => 'Label dicetak — menunggu penempelan', 'label_attached' => 'Label ditempel — menunggu penempatan', 'placed' => 'Ditempatkan — menunggu serah terima', 'active' => 'Barang Aktif'];

    public function advance(Asset $asset, User $user, string $step, array $data): void
    {
        abort_unless($user->hasAnyRole([User::ROLE_SUPERADMIN, User::ROLE_OPERASIONAL]), 403);
        DB::transaction(function () use ($asset, $user, $step, $data): void {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            $transitions = ['print' => ['registered', 'label_printed', 'label_printed_at', 'Label selesai dicetak'], 'attach' => ['label_printed', 'label_attached', 'label_attached_at', 'Label telah ditempel pada aset'], 'place' => ['label_attached', 'placed', 'placed_at', 'Aset ditempatkan'], 'handover' => ['placed', 'active', 'handed_over_at', 'Serah terima selesai; barang aktif']];
            if (! isset($transitions[$step])) {
                abort(404);
            }
            [$from, $to, $timestamp, $message] = $transitions[$step];
            if ($asset->activation_stage !== $from) {
                throw ValidationException::withMessages(['stage' => 'Tahap sebelumnya belum selesai atau tahap ini sudah dicatat.']);
            }
            $changes = ['activation_stage' => $to, $timestamp => now()];
            if ($step === 'place') {
                $changes += ['location_id' => $data['location_id'], 'room_id' => $data['room_id'] ?? null];
            }
            if ($step === 'handover') {
                $changes += ['recipient_name' => $data['recipient_name'], 'handover_reference' => $data['handover_reference']];
            }
            $asset->update($changes);
            $details = array_intersect_key($data, array_flip(['location_id', 'room_id', 'recipient_name', 'handover_reference', 'notes']));
            if ($step === 'handover') {
                $asset->load(['location', 'room', 'category']);
                $details['handover_snapshot'] = (new AssetHandoverDocument)->snapshot($asset, $user->name, $data['notes'] ?? null);
            }
            $asset->activities()->create(['user_id' => $user->id, 'activity_type' => 'activation_'.$step, 'message' => $message, 'details' => json_encode($details, JSON_UNESCAPED_UNICODE), 'occurred_at' => now()]);
        });
    }
}
