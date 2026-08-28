@extends('layouts.app')

@section('title', 'Edit Setting Jam Kerja - ADMS')
@section('page-title', 'Edit Setting Jam Kerja')
@section('page-subtitle', 'Edit pengaturan jam kerja')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-cog text-blue-600 mr-2"></i>
                Edit: {{ $setting->name }}
            </h3>
        </div>

        <form action="{{ route('settings.update', $setting) }}" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Setting <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $setting->name) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Golongan</label>
                    <select name="golongan_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Global (Semua Golongan) --</option>
                        @foreach($golongans as $golongan)
                        <option value="{{ $golongan->id }}" {{ old('golongan_id', $setting->golongan_id) == $golongan->id ? 'selected' : '' }}>
                            {{ $golongan->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('golongan_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hari</label>
                @php
                    $selectedDays = old('day', $setting->day ? explode(',', $setting->day) : []) ?? [];
                @endphp
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $d)
                    <label class="flex items-center px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-blue-500">
                        <input type="checkbox" name="day[]" value="{{ $d }}" {{ in_array($d, $selectedDays) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">{{ $d }}</span>
                    </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-1">Pilih satu atau beberapa hari (Senin–Sabtu). Kosongkan untuk berlaku setiap hari.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-sign-in-alt text-green-600 mr-1"></i>Jam Masuk <span class="text-red-500">*</span>
                    </label>
                    <input type="time" name="check_in_time" value="{{ old('check_in_time', substr($setting->check_in_time, 0, 5)) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @error('check_in_time') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-sign-out-alt text-red-600 mr-1"></i>Jam Keluar <span class="text-red-500">*</span>
                    </label>
                    <input type="time" name="check_out_time" value="{{ old('check_out_time', substr($setting->check_out_time, 0, 5)) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @error('check_out_time') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-utensils text-yellow-600 mr-1"></i>Keluar Istirahat <span class="text-red-500">*</span>
                    </label>
                    <input type="time" name="break_out_time" value="{{ old('break_out_time', substr($setting->break_out_time, 0, 5)) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @error('break_out_time') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-utensils text-yellow-600 mr-1"></i>Masuk Istirahat <span class="text-red-500">*</span>
                    </label>
                    <input type="time" name="break_in_time" value="{{ old('break_in_time', substr($setting->break_in_time, 0, 5)) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @error('break_in_time') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Toleransi Telat (menit) <span class="text-red-500">*</span></label>
                    <input type="number" name="late_tolerance_minutes" value="{{ old('late_tolerance_minutes', $setting->late_tolerance_minutes) }}" required min="0" max="120"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Waktu toleransi sebelum dianggap telat</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Threshold Lembur (menit) <span class="text-red-500">*</span></label>
                    <input type="number" name="overtime_threshold_minutes" value="{{ old('overtime_threshold_minutes', $setting->overtime_threshold_minutes) }}" required min="0" max="240"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Waktu setelah jam keluar untuk dianggap lembur</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="2"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('description', $setting->description) }}</textarea>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $setting->is_active) ? 'checked' : '' }}
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label class="ml-2 text-sm text-gray-700">Setting ini aktif</label>
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Update
                </button>
                <a href="{{ route('settings.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
