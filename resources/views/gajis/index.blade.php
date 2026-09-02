@extends('layouts.app')

@section('title', 'Master Gaji - ADMS')
@section('page-title', 'Master Gaji')
@section('page-subtitle', 'Data gaji pokok karyawan')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-money-bill-wave text-blue-600 mr-2"></i>
                Daftar Gaji
            </h3>
            <a href="{{ route('gajis.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Tambah Gaji
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nominal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($gajis as $gaji)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $gaji->name }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">Rp {{ number_format($gaji->amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('gajis.edit', $gaji) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('gajis.destroy', $gaji) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus gaji ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center">
                            <i class="fas fa-money-bill-wave text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Belum ada data gaji</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
