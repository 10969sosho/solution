@extends('layouts.app')

@section('title', 'Edit Gaji - ADMS')
@section('page-title', 'Edit Gaji')
@section('page-subtitle', 'Ubah gaji ' . $employee->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-money-bill-wave text-blue-600 mr-2"></i>
                Edit Gaji: {{ $employee->name }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">{{ $employee->employee_id }}</p>
        </div>

        <form action="{{ route('gajis.update', $employee) }}" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gaji Pokok <span class="text-red-500">*</span></label>
                <input type="number" name="salary" value="{{ old('salary', $employee->salary) }}" step="0.01" min="0" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: 2000000">
                @error('salary') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tier Gaji</label>
                <input type="text" name="salary_tier" value="{{ old('salary_tier', $employee->salary_tier) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: A / B / C">
                @error('salary_tier') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
                <a href="{{ route('gajis.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
