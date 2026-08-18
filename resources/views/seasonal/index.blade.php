@extends('layouts.app')

@section('title', 'Jam Kerja Musiman - ADMS')
@section('page-title', 'Jam Kerja Musiman')
@section('page-subtitle', 'Pengaturan jam kerja musiman (bulk update)')

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
                <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>
                Daftar Jam Kerja Musiman
            </h3>
            <a href="{{ route('seasonal.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Tambah Musiman
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perubahan Masuk</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perubahan Pulang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Force Masuk</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($schedules as $schedule)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $schedule->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ $schedule->start_date->format('d M Y') }}
                            @if($schedule->end_date)
                            - {{ $schedule->end_date->format('d M Y') }}
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            @if($schedule->check_in_delta_minutes == 0) Tetap
                            @else {{ ($schedule->check_in_delta_minutes > 0 ? '+' : '') . $schedule->check_in_delta_minutes . ' min' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            @if($schedule->check_out_delta_minutes == 0) Tetap
                            @else {{ ($schedule->check_out_delta_minutes > 0 ? '+' : '') . $schedule->check_out_delta_minutes . ' min' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $schedule->force_check_in_time ? substr($schedule->force_check_in_time, 0, 5) : '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @if($schedule->is_active) bg-green-100 text-green-800 @else bg-gray-100 text-gray-800 @endif">
                                {{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('seasonal.edit', $schedule) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('seasonal.destroy', $schedule) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus?')">
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
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-calendar-alt text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Belum ada jam kerja musiman</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection