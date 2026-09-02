<?php

namespace App\Http\Controllers;

use App\Models\PotonganTerlambat;
use Illuminate\Http\Request;

class PotonganTerlambatController extends Controller
{
    public function index()
    {
        $potongans = PotonganTerlambat::orderBy('golongan_type')
            ->orderBy('min_minutes')
            ->get();

        $grouped = $potongans->groupBy('golongan_type');

        return view('potongan-terlamats.index', compact('potongans', 'grouped'));
    }

    public function create()
    {
        return view('potongan-terlamats.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'golongan_type' => 'required|in:gudang_kandang,mandor_admin',
            'min_minutes' => 'required|integer|min:0',
            'max_minutes' => 'nullable|integer|min:0|gte:min_minutes',
            'amount' => 'required|numeric|min:0',
        ]);

        PotonganTerlambat::create($validated);

        return redirect()->route('potongan-terlamats.index')->with('success', 'Potongan terlambat berhasil ditambahkan');
    }

    public function edit(PotonganTerlambat $potongan_terlambat)
    {
        return view('potongan-terlamats.edit', ['potongan' => $potongan_terlambat]);
    }

    public function update(Request $request, PotonganTerlambat $potongan_terlambat)
    {
        $validated = $request->validate([
            'golongan_type' => 'required|in:gudang_kandang,mandor_admin',
            'min_minutes' => 'required|integer|min:0',
            'max_minutes' => 'nullable|integer|min:0|gte:min_minutes',
            'amount' => 'required|numeric|min:0',
        ]);

        $potongan_terlambat->update($validated);

        return redirect()->route('potongan-terlamats.index')->with('success', 'Potongan terlambat berhasil diupdate');
    }

    public function destroy(PotonganTerlambat $potongan_terlambat)
    {
        $potongan_terlambat->delete();

        return redirect()->route('potongan-terlamats.index')->with('success', 'Potongan terlambat berhasil dihapus');
    }
}
