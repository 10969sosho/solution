@extends('layouts.app')

@section('title', 'Detail Karyawan - ADMS')
@section('page-title', 'Detail Karyawan')
@section('page-subtitle', 'Lihat data karyawan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-user text-blue-600 mr-2"></i>
                {{ $employee->name }}
            </h3>
        </div>

        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">ID Karyawan</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $employee->employee_id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Status</label>
                    <p class="mt-1 text-sm">
                        @if($employee->status === 'active')
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Aktif</span>
                        @elseif($employee->status === 'inactive')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">Nonaktif</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">Resign</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Golongan</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $employee->golongan?->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Jabatan</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $employee->jabatan?->name ?? $employee->position ?? '-' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Lokasi</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $employee->lokasi?->name ?? $employee->location ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Departemen</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $employee->department ?? '-' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Tanggal Bergabung</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $employee->join_date?->format('d M Y') ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Jam Masuk Normal</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $employee->jam_masuk_normal ?? '-' }}</p>
                </div>
            </div>

            @if($employee->tanggal_keluar)
            <div>
                <label class="block text-sm font-medium text-gray-500">Tanggal Keluar</label>
                <p class="mt-1 text-sm text-gray-900">{{ $employee->tanggal_keluar->format('d M Y') }}</p>
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Telepon</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $employee->phone ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Email</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $employee->email ?? '-' }}</p>
                </div>
            </div>

            @if(auth()->user()?->isSuperAdmin())
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Gaji Pokok</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $employee->gaji?->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Nominal Gaji</label>
                    <p class="mt-1 text-sm text-gray-900">Rp {{ number_format($employee->gaji?->amount ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            @endif

            @if($employee->address)
            <div>
                <label class="block text-sm font-medium text-gray-500">Alamat</label>
                <p class="mt-1 text-sm text-gray-900">{{ $employee->address }}</p>
            </div>
            @endif

            <div class="flex space-x-3 pt-4">
                <a href="{{ route('employees.edit', $employee) }}" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
                <a href="{{ route('employees.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Kembali
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
