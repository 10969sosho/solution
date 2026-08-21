<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use Illuminate\Http\Request;

class JabatanController extends Controller
{
    public function index()
    {
        $jabatans = Jabatan::orderBy('name')->get();
        return view('jabatans.index', compact('jabatans'));
    }

    public function create()
    {
        return view('jabatans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:jabatans',
        ]);

        Jabatan::create($validated);

        return redirect()->route('jabatans.index')->with('success', 'Jabatan berhasil ditambahkan');
    }

    public function edit(Jabatan $jabatan)
    {
        return view('jabatans.edit', compact('jabatan'));
    }

    public function update(Request $request, Jabatan $jabatan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:jabatans,code,' . $jabatan->id,
        ]);

        $jabatan->update($validated);

        return redirect()->route('jabatans.index')->with('success', 'Jabatan berhasil diupdate');
    }

    public function destroy(Jabatan $jabatan)
    {
        $jabatan->delete();

        return redirect()->route('jabatans.index')->with('success', 'Jabatan berhasil dihapus');
    }
}