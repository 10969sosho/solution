@extends('layouts.app')

@section('title', 'Payroll - ADMS')
@section('page-title', 'Payroll / Penggajian')
@section('page-subtitle', 'Perhitungan gaji karyawan')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
    @endif

    <!-- Filter & Aksi -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('payrolls.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="month" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Tampilkan
                </button>
            </div>
            <div class="flex items-end justify-end">
                <a href="{{ route('payrolls.thr', ['year' => $year]) }}" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors mr-2">
                    <i class="fas fa-gift mr-2"></i>THR
                </a>
                <form method="POST" action="{{ route('payrolls.generate') }}" onsubmit="return confirm('Generate ulang payroll untuk semua karyawan periode ini? Payroll yang sudah paid tidak akan berubah.')">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-calculator mr-2"></i>Generate Payroll
                    </button>
                </form>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-money-check-alt text-blue-600 mr-2"></i>
                Payroll - {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gaji Pokok</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Potongan</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Bonus</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gaji Bersih</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($payrolls as $payroll)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4">
                            <div class="font-medium text-gray-800">{{ $payroll->employee?->name }}</div>
                            <div class="text-xs text-gray-500">ID: {{ $payroll->employee?->employee_id }}</div>
                        </td>
                        <td class="px-4 py-4 text-right text-sm text-gray-700">Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right text-sm text-red-600">Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right text-sm text-green-600">Rp {{ number_format($payroll->total_incentive, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right text-sm font-bold text-gray-800">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                        <td class="px-4 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @if($payroll->status === 'paid') bg-green-100 text-green-800 @else bg-yellow-100 text-yellow-800 @endif">
                                {{ $payroll->status === 'paid' ? 'Dibayar' : 'Draft' }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('payrolls.show', $payroll) }}" class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></a>
                                @if($payroll->status === 'draft')
                                <form action="{{ route('payrolls.paid', $payroll) }}" method="POST" class="inline" onsubmit="return confirm('Tandai payroll ini sudah dibayar?')">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800" title="Tandai dibayar"><i class="fas fa-check-circle"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-money-check-alt text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Belum ada payroll untuk periode ini. Klik "Generate Payroll".</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection