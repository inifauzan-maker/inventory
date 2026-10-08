<?php

namespace App;

use App\Models\Asset;
use App\Models\AssetMutation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetMutationService
{
    public function move(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $asset = Asset::whereKey($data['asset_id'])->lockForUpdate()->firstOrFail();
            foreach (['from_location_id' => 'location_id', 'from_room_id' => 'room_id'] as $input => $field) {
                if (isset($data[$input]) && (int) $data[$input] !== (int) $asset->$field) {
                    throw ValidationException::withMessages([$input => 'Posisi asal berbeda dari posisi terkini aset. Muat ulang formulir.']);
                }
            }
            $location = $data['to_location_id'] ?? $asset->location_id;
            $room = $data['to_room_id'] ?? null;
            if ($room && ! Room::whereKey($room)->where('location_id', $location)->exists()) {
                throw ValidationException::withMessages(['to_room_id' => 'Ruangan tujuan harus berada pada lokasi tujuan.']);
            }
            AssetMutation::create([
                'asset_id' => $asset->id,
                'from_location_id' => $asset->location_id,
                'from_room_id' => $asset->room_id,
                'to_location_id' => $location,
                'to_room_id' => $room,
                'moved_by_user_id' => $user->id,
                'notes' => $data['notes'] ?? null,
                'moved_at' => now(),
            ]);
            $asset->update(['location_id' => $location, 'room_id' => $room]);
            $asset->activities()->create([
                'user_id' => $user->id,
                'activity_type' => 'mutation',
                'message' => 'Mutasi aset',
                'details' => $data['notes'] ?? 'Lokasi berubah',
                'occurred_at' => now(),
            ]);
        });
    }
}
