@extends('layouts.app')

@section('title', 'Setting Jam Kerja - ADMS')
@section('page-title', 'Setting Jam Kerja')
@section('page-subtitle', 'Kelola pengaturan jam kerja')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-cog text-blue-600 mr-2"></i>
                Daftar Setting Jam Kerja
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Masuk</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Keluar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Toleransi Telat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($settings as $setting)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $setting->name }}</div>
                            <div class="text-xs text-gray-500">{{ $setting->description }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <i class="fas fa-sign-in-alt text-green-600 mr-1"></i>
                            {{ \Carbon\Carbon::parse($setting->check_in_time)->format('H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <i class="fas fa-sign-out-alt text-red-600 mr-1"></i>
                            {{ \Carbon\Carbon::parse($setting->check_out_time)->format('H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $setting->late_tolerance_minutes }} menit</td>
                        <td class="px-6 py-4">
                            @if($setting->is_active)
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Aktif</span>
                            @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('settings.edit', $setting) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i class="fas fa-cog text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Belum ada setting jam kerja</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
