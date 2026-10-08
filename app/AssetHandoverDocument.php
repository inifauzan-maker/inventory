<?php

namespace App;

use App\Models\Asset;

class AssetHandoverDocument
{
    public function snapshot(Asset $asset, string $sender, ?string $notes): array
    {
        return [
            'reference' => $asset->handover_reference,
            'date' => $asset->handed_over_at?->format('d/m/Y H:i'),
            'sender' => $sender,
            'recipient' => $asset->recipient_name,
            'asset_number' => $asset->asset_number,
            'name' => $asset->name,
            'category' => $asset->category?->name,
            'serial' => $asset->serial_number,
            'condition' => $asset->condition,
            'location' => $asset->location?->name,
            'room' => $asset->room?->name,
            'notes' => $notes,
        ];
    }

    public function data(Asset $asset): array
    {
        abort_unless($asset->handed_over_at && $asset->recipient_name && $asset->handover_reference, 409, 'Serah terima harus selesai sebelum BAST tersedia.');
        $activity = $asset->activities()->with('user')->where('activity_type', 'activation_handover')->latest('id')->first();
        $details = json_decode($activity?->details ?? '{}', true);
        $snapshot = is_array($details) ? ($details['handover_snapshot'] ?? null) : null;
        if (is_array($snapshot) && isset($snapshot['reference'], $snapshot['asset_number'], $snapshot['date'])) {
            return ['snapshot' => true, 'data' => $snapshot];
        }
        $asset->load(['location', 'room', 'category']);

        return ['snapshot' => false, 'data' => $this->snapshot($asset, $activity?->user?->name ?? 'Belum tercatat', is_array($details) ? ($details['notes'] ?? null) : null)];
    }
}
