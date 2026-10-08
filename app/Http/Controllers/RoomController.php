<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        return view('master.rooms.index', [
            'rooms' => Room::with('location')->orderBy('code')->get(),
            'locations' => Location::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms')->where(fn ($query) => $query->where('location_id', $request->input('location_id'))),
            ],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        Room::create($validated);

        return redirect()->route('master.ruangan.index');
    }
}
