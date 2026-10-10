@extends('layouts.app')

@section('title', 'Master Gaji - ADMS')
@section('page-title', 'Master Gaji')
@section('page-subtitle', 'Kelola gaji pokok karyawan')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-money-bill-wave text-blue-600 mr-2"></i>
                Daftar Gaji Karyawan
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gaji Pokok</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($employees as $employee)
                    @php
                        $canManage = auth()->user()->isSuperAdmin() || !$employee->isMandorOrAdmin();
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-800">{{ $employee->name }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $employee->employee_id }}
                                @if($employee->isMandorOrAdmin())
                                    <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 font-semibold">Mandor/Admin</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $employee->jabatan?->name ?? $employee->position ?? '-' }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium text-gray-800">
                            @if($canManage)
                                Rp {{ number_format($employee->salary ?? 0, 0, ',', '.') }}
                            @else
                                <span class="text-gray-400 font-mono tracking-widest font-bold">••••••</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($canManage)
                                <a href="{{ route('gajis.edit', $employee) }}" class="text-blue-600 hover:text-blue-800 transition" title="Edit Gaji Pokok">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @else
                                <span class="text-gray-300 cursor-not-allowed" title="Akses terbatas untuk Admin"><i class="fas fa-lock"></i></span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <i class="fas fa-money-bill-wave text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Belum ada data karyawan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
