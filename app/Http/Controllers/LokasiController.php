<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use Illuminate\Http\Request;

class LokasiController extends Controller
{
    public function index()
    {
        $lokasis = Lokasi::orderBy('name')->get();
        return view('lokasis.index', compact('lokasis'));
    }

    public function create()
    {
        return view('lokasis.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Lokasi::create($validated);

        return redirect()->route('lokasis.index')->with('success', 'Lokasi berhasil ditambahkan');
    }

    public function edit(Lokasi $lokasi)
    {
        return view('lokasis.edit', compact('lokasi'));
    }

    public function update(Request $request, Lokasi $lokasi)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $lokasi->update($validated);

        return redirect()->route('lokasis.index')->with('success', 'Lokasi berhasil diupdate');
    }

    public function destroy(Lokasi $lokasi)
    {
        $lokasi->delete();

        return redirect()->route('lokasis.index')->with('success', 'Lokasi berhasil dihapus');
    }
}