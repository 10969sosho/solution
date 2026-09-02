<?php

namespace App\Http\Controllers;

use App\Models\Gaji;
use Illuminate\Http\Request;

class GajiController extends Controller
{
    public function index()
    {
        $gajis = Gaji::orderBy('name')->get();
        return view('gajis.index', compact('gajis'));
    }

    public function create()
    {
        return view('gajis.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        Gaji::create($validated);

        return redirect()->route('gajis.index')->with('success', 'Gaji berhasil ditambahkan');
    }

    public function edit(Gaji $gaji)
    {
        return view('gajis.edit', compact('gaji'));
    }

    public function update(Request $request, Gaji $gaji)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        $gaji->update($validated);

        return redirect()->route('gajis.index')->with('success', 'Gaji berhasil diupdate');
    }

    public function destroy(Gaji $gaji)
    {
        $gaji->delete();

        return redirect()->route('gajis.index')->with('success', 'Gaji berhasil dihapus');
    }
}
