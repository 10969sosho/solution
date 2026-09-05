@extends('layouts.app')

@section('title', 'Master Karyawan - ADMS')
@section('page-title', 'Master Karyawan')
@section('page-subtitle', 'Kelola data karyawan')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('employees.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Nama, ID, atau Email">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                    <select name="jabatan_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Semua --</option>
                        @foreach($jabatans as $jabatan)
                        <option value="{{ $jabatan->id }}" {{ request('jabatan_id') == $jabatan->id ? 'selected' : '' }}>{{ $jabatan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Golongan</label>
                    <select name="golongan_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Semua --</option>
                        @foreach($golongans as $golongan)
                        <option value="{{ $golongan->id }}" {{ request('golongan_id') == $golongan->id ? 'selected' : '' }}>{{ $golongan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                    <select name="lokasi_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Semua --</option>
                        @foreach($lokasis as $lokasi)
                        <option value="{{ $lokasi->id }}" {{ request('lokasi_id') == $lokasi->id ? 'selected' : '' }}>{{ $lokasi->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Bergabung Dari</label>
                    <input type="date" name="join_date_from" value="{{ request('join_date_from') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Bergabung Sampai</label>
                    <input type="date" name="join_date_to" value="{{ request('join_date_to') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <a href="{{ route('employees.index') }}" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-center">
                        <i class="fas fa-times mr-2"></i>Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-users text-blue-600 mr-2"></i>
                Daftar Karyawan
                <span class="text-sm font-normal text-gray-500 ml-2">({{ $employees->total() }} data)</span>
            </h3>
            <a href="{{ route('employees.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Tambah Karyawan
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Golongan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lokasi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Gabung</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($employees as $employee)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $employee->employee_id }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ $employee->name }}</div>
                            <div class="text-xs text-gray-500">{{ $employee->email }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $employee->jabatan->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($employee->golongan)
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">{{ $employee->golongan->name }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">{{ $employee->lokasi->name ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $employee->join_date ? $employee->join_date->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @if($employee->status === 'active') bg-green-100 text-green-800
                                @elseif($employee->status === 'inactive') bg-gray-100 text-gray-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst($employee->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex space-x-2">
                                <a href="{{ route('employees.edit', $employee) }}" class="text-blue-600 hover:text-blue-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus karyawan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Belum ada data karyawan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $employees->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
