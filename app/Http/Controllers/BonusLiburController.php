<?php

namespace App\Http\Controllers;

use App\Models\BonusLiburSetting;
use App\Models\Employee;
use Illuminate\Http\Request;

class BonusLiburController extends Controller
{
    public function index()
    {
        $setting = BonusLiburSetting::current();

        $employees = Employee::where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($setting) {
                $jatah = (float) $employee->getEffectiveLeaveQuota();
                $salary = (float) $employee->salary;
                $batas = (float) $setting->batas_gaji;

                if ($salary < $batas) {
                    $perDay = (float) $setting->bonus_satu_hari;
                    $fullDays = floor($jatah);
                    $hasHalf = ($jatah - $fullDays) >= 0.5;
                    $estimasi = ($fullDays * $perDay)
                        + ($hasHalf ? (float) $setting->bonus_setengah_hari : 0);
                } else {
                    $perDay = ($salary / 30) * (float) $setting->pengali_bonus;
                    $estimasi = $perDay * $jatah;
                }

                return [
                    'employee' => $employee,
                    'jatah' => $jatah,
                    'per_day' => $perDay,
                    'estimasi' => $estimasi,
                ];
            });

        return view('bonus-libur.index', compact('setting', 'employees'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'batas_gaji' => 'required|numeric|min:0',
            'bonus_satu_hari' => 'required|numeric|min:0',
            'bonus_setengah_hari' => 'required|numeric|min:0',
            'pengali_bonus' => 'required|numeric|min:0|max:10',
        ]);

        BonusLiburSetting::current()->update($validated);

        return redirect()->route('bonus-libur.index')->with('success', 'Pengaturan Master Bonus Libur berhasil disimpan');
    }
}
