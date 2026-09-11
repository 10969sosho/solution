@extends('layouts.app')

@section('title', 'Laporan Tahunan - Rekapitulasi Libur Karyawan')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Tahunan: Pencatatan Hari Libur</h1>
            <p class="text-sm text-gray-500">Evaluasi pencatatan hari libur karyawan per periode 6 bulan (Semester 1 &amp; Semester 2)</p>
        </div>

        <form method="GET" action="{{ route('reports.yearly') }}" class="flex items-center gap-2">
            <select name="year" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                @for($y = now()->year - 3; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                @endfor
            </select>
            @if(count($locations) > 0)
                <select name="location" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc }}" {{ $location == $loc ? 'selected' : '' }}>{{ $loc }}</option>
                    @endforeach
                </select>
            @endif
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
        </form>
    </div>

    <!-- Tabel Evaluasi Semesteran -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-700 font-semibold uppercase text-xs">
                <tr>
                    <th class="px-6 py-3 text-left">Nama Karyawan</th>
                    <th class="px-6 py-3 text-left">Jabatan / Posisi</th>
                    <th class="px-6 py-3 text-center">Semester 1 (Jan - Jun)</th>
                    <th class="px-6 py-3 text-center">Semester 2 (Jul - Des)</th>
                    <th class="px-6 py-3 text-center">Total Libur Setahun</th>
                    <th class="px-6 py-3 text-center">Jatah Kuota Setahun</th>
                    <th class="px-6 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reportData as $row)
                    @php $emp = $row['employee']; @endphp
                    <tr class="hover:bg-blue-50/50 transition">
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <div>{{ $emp->name }}</div>
                            <div class="text-xs text-gray-500">{{ $emp->employee_id }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $emp->position ?? $emp->department ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-blue-600">
                            {{ $row['sem1_libur'] }} Hari
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-indigo-600">
                            {{ $row['sem2_libur'] }} Hari
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-gray-800">
                            {{ $row['total_libur'] }} Hari
                        </td>
                        <td class="px-6 py-4 text-center text-gray-600">
                            {{ $row['leave_quota_yearly'] }} Hari
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('attendance.employee-detail', ['employee_id' => $emp->id, 'year' => $year]) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition">
                                <i class="fas fa-eye mr-1"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">Belum ada data untuk tahun terpilih.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
