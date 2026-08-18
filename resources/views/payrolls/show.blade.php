@extends('layouts.app')

@section('title', 'Detail Payroll - ADMS')
@section('page-title', 'Detail Payroll')
@section('page-subtitle', 'Rincian perhitungan gaji')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-800">{{ $payroll->employee?->name }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    ID: {{ $payroll->employee?->employee_id }}
                    | Jabatan: {{ $payroll->employee?->position ?? '-' }}
                    | Periode: {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month, 1)->format('F Y') }}
                </p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-medium
                @if($payroll->status === 'paid') bg-green-100 text-green-800 @else bg-yellow-100 text-yellow-800 @endif">
                {{ $payroll->status === 'paid' ? 'Dibayar' : 'Draft' }}
            </span>
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 mb-1">Gaji Pokok</p>
            <p class="text-2xl font-bold text-gray-800">Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
            <p class="text-sm text-gray-500 mb-1">Total Potongan</p>
            <p class="text-2xl font-bold text-red-600">Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 mb-1">Total Bonus</p>
            <p class="text-2xl font-bold text-green-600">Rp {{ number_format($payroll->total_incentive, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500 mb-1">Gaji Bersih</p>
            <p class="text-2xl font-bold text-purple-700">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Rincian Potongan -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-minus-circle text-red-500 mr-2"></i>Komponen Potongan
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-700">Denda Keterlambatan</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">Rp {{ number_format($payroll->late_deduction, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-700">Cicilan Pinjaman / Bon</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">Rp {{ number_format($payroll->loan_deduction, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-700">Pemotongan Hari Libur / Ketidakhadiran</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">Rp {{ number_format($payroll->absence_deduction, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-4 text-sm font-bold text-gray-800">Total Potongan</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-red-600">Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Rincian Bonus -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-plus-circle text-green-500 mr-2"></i>Komponen Bonus
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-700">Bonus Kehadiran (tidak mengambil jatah libur)</td>
                        <td class="px-6 py-4 text-right text-sm text-green-600">Rp {{ number_format($payroll->attendance_bonus, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-4 text-sm font-bold text-gray-800">Total Bonus</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-green-600">Rp {{ number_format($payroll->total_incentive, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Breakdown Absensi -->
    @if($payroll->breakdown)
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-chart-bar text-blue-500 mr-2"></i>Data Absensi (Otomatis)
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-700">Hari Hadir</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">{{ $payroll->breakdown['days_present'] }} hari</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-700">Total Jam Kerja</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">{{ intdiv($payroll->breakdown['total_work_minutes'], 60) }}j {{ $payroll->breakdown['total_work_minutes'] % 60 }}m</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-700">Total Terlambat</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">{{ $payroll->breakdown['total_late_minutes'] }} menit ({{ $payroll->breakdown['days_late'] }} hari)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection