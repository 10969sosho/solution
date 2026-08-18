@extends('layouts.app')

@section('title', 'Kalkulasi THR - ADMS')
@section('page-title', 'Kalkulasi THR')
@section('page-subtitle', 'Perhitungan THR dengan diferensiasi masa kerja')

@section('content')
<div class="space-y-6">
    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('payrolls.thr') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Tampilkan
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-gift text-purple-600 mr-2"></i>
                THR - {{ $year }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Masa kerja &ge; 5 tahun: THR penuh (1x gaji pokok). Masa kerja &lt; 5 tahun: proporsional.
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Masuk Kerja</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Masa Kerja</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gaji Pokok</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">THR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($rows as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $row['name'] }}</div>
                            <div class="text-xs text-gray-500">ID: {{ $row['employee_id'] }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $row['position'] ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $row['join_date'] ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($row['long_service'])
                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">&ge; 5 tahun</span>
                            @else
                            <span class="text-sm text-gray-700">{{ intdiv($row['tenure_months'], 12) }}th {{ $row['tenure_months'] % 12 }}bln</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">Rp {{ number_format($row['salary'], 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-purple-700">Rp {{ number_format($row['thr'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <p class="text-gray-500">Tidak ada karyawan aktif</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection