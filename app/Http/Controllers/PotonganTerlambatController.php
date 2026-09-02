<?php

namespace App\Http\Controllers;

use App\Models\Golongan;
use App\Models\PotonganTerlambat;
use Illuminate\Http\Request;

class PotonganTerlambatController extends Controller
{
    public function index()
    {
        $potongans = PotonganTerlambat::with('golongan')
            ->orderBy('golongan_id')
            ->orderBy('min_minutes')
            ->get();

        $grouped = $potongans->groupBy('golongan_id');

        return view('potongan-terlambat.index', compact('potongans', 'grouped'));
    }

    public function create()
    {
        $golongans = Golongan::orderBy('name')->get();
        return view('potongan-terlambat.create', compact('golongans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'golongan_id' => 'required|exists:golongans,id',
            'min_minutes' => 'required|integer|min:0',
            'max_minutes' => 'nullable|integer|min:0|gte:min_minutes',
            'amount' => 'required|numeric|min:0',
        ]);

        PotonganTerlambat::create($validated);

        return redirect()->route('potongan-terlambat.index')->with('success', 'Potongan terlambat berhasil ditambahkan');
    }

    public function edit(PotonganTerlambat $potongan_terlambat)
    {
        $golongans = Golongan::orderBy('name')->get();
        return view('potongan-terlambat.edit', ['potongan' => $potongan_terlambat, 'golongans' => $golongans]);
    }

    public function update(Request $request, PotonganTerlambat $potongan_terlambat)
    {
        $validated = $request->validate([
            'golongan_id' => 'required|exists:golongans,id',
            'min_minutes' => 'required|integer|min:0',
            'max_minutes' => 'nullable|integer|min:0|gte:min_minutes',
            'amount' => 'required|numeric|min:0',
        ]);

        $potongan_terlambat->update($validated);

        return redirect()->route('potongan-terlambat.index')->with('success', 'Potongan terlambat berhasil diupdate');
    }

    public function destroy(PotonganTerlambat $potongan_terlambat)
    {
        $potongan_terlambat->delete();

        return redirect()->route('potongan-terlambat.index')->with('success', 'Potongan terlambat berhasil dihapus');
    }
}
