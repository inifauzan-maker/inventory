@extends('layouts.app-shell')

@section('page-title', 'Audit Trail')
@section('header-title', 'Audit Trail')

@section('content')
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h2 class="text-lg font-semibold text-slate-900">Riwayat Aktivitas Aset</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Aset</th>
                        <th class="px-4 py-3">Aktivitas</th>
                        <th class="px-4 py-3">Perubahan</th>
                        <th class="px-4 py-3">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($activities as $activity)
                        <tr>
                            <td class="px-4 py-3 text-slate-700">{{ $activity->occurred_at?->format('d-m-Y H:i') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $activity->asset?->name ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $activity->asset?->asset_number ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-sky-100 px-2 py-1 text-xs font-medium text-sky-700">
                                    {{ $activity->activity_type ?? 'activity' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $activity->message ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $activity->user?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada aktivitas yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
