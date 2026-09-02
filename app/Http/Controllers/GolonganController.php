<?php

namespace App\Http\Controllers;

use App\Models\Golongan;
use Illuminate\Http\Request;

class GolonganController extends Controller
{
    public function index()
    {
        $golongans = Golongan::orderBy('name')->get();
        return view('golongans.index', compact('golongans'));
    }

    public function create()
    {
        return view('golongans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:gudang_kandang,mandor_admin',
        ]);

        Golongan::create($validated);

        return redirect()->route('golongans.index')->with('success', 'Golongan berhasil ditambahkan');
    }

    public function edit(Golongan $golongan)
    {
        return view('golongans.edit', compact('golongan'));
    }

    public function update(Request $request, Golongan $golongan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:gudang_kandang,mandor_admin',
        ]);

        $golongan->update($validated);

        return redirect()->route('golongans.index')->with('success', 'Golongan berhasil diupdate');
    }

    public function destroy(Golongan $golongan)
    {
        $golongan->delete();

        return redirect()->route('golongans.index')->with('success', 'Golongan berhasil dihapus');
    }
}