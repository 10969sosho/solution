<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'position',
        'department',
        'location',
        'golongan_id',
        'jabatan_id',
        'lokasi_id',
        'gaji_id',
        'tanggal_keluar',
        'jam_masuk_normal',
        'phone',
        'email',
        'join_date',
        'status',
        'salary',
        'salary_tier',
        'address',
        'bank_name',
        'account_number',
        'account_holder',
        'payment_method',
        'leave_quota',
        'is_night_guard',
    ];

    protected $casts = [
        'join_date' => 'date',
        'salary' => 'decimal:2',
        'tanggal_keluar' => 'date',
        'jam_masuk_normal' => 'string',
        'is_night_guard' => 'boolean',
        'leave_quota' => 'integer',
    ];

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'user_id', 'employee_id');
    }

    public function dailyAttendances()
    {
        return $this->hasMany(DailyAttendance::class);
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function permits()
    {
        return $this->hasMany(Permit::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function activeSchedule()
    {
        return $this->hasOne(EmployeeSchedule::class)->where('is_active', true)->latest('effective_from');
    }

    public function golongan()
    {
        return $this->belongsTo(Golongan::class);
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function gaji()
    {
        return $this->belongsTo(Gaji::class);
    }

    /**
     * Tentukan jatah libur bulanan efektif (Admin/Mandor = 2, Jaga Malam/AGK = 1).
     */
    public function getEffectiveLeaveQuota(): int
    {
        if (!is_null($this->leave_quota)) {
            return (int) $this->leave_quota;
        }

        $pos = strtolower($this->position ?? '');
        $jabatanName = strtolower($this->jabatan?->name ?? '');
        $golType = $this->golongan?->type;

        if (
            str_contains($pos, 'admin') ||
            str_contains($pos, 'mandor') ||
            str_contains($jabatanName, 'admin') ||
            str_contains($jabatanName, 'mandor') ||
            $golType === 'mandor_admin'
        ) {
            return (int) config('payroll_rules.default_leave_quota.admin_mandor', 2);
        }

        return (int) config('payroll_rules.default_leave_quota.jaga_malam_agk', 1);
    }

    public function isMandor(): bool
    {
        $pos = strtolower($this->position ?? '');
        $jabatanName = strtolower($this->jabatan?->name ?? '');
        return str_contains($pos, 'mandor') || str_contains($jabatanName, 'mandor');
    }

    public function isMandorOrAdmin(): bool
    {
        if ($this->golongan?->type === 'mandor_admin') {
            return true;
        }

        $pos = strtolower($this->position ?? '');
        $jabatanName = strtolower($this->jabatan?->name ?? '');
        $golName = strtolower($this->golongan?->name ?? '');

        return str_contains($pos, 'mandor') || str_contains($pos, 'admin')
            || str_contains($jabatanName, 'mandor') || str_contains($jabatanName, 'admin')
            || str_contains($golName, 'mandor') || str_contains($golName, 'admin')
            || (bool) $this->golongan?->is_confidential;
    }

    public function isConfidential(): bool
    {
        return (bool) ($this->golongan?->is_confidential ?? false);
    }

    public function nightGuards()
    {
        return $this->hasMany(NightGuard::class);
    }

    /**
     * Hitung total sisa pinjaman karyawan saat ini (principal aktif - total payments).
     */
    public function getTotalRemainingLoan(): float
    {
        $loans = $this->loans()->with('payments')->get();
        return (float) $loans->sum(fn ($loan) => $loan->remaining_balance);
    }

    /**
     * Jatah libur bulanan dengan carry-over sisa libur bulan sebelumnya.
     * Tiap tanggal 1 default reset tanpa menghilangkan sisa libur bulan lalu.
     */
    public function getEffectiveLeaveQuotaForMonth(int $year, int $month): float
    {
        $defaultQuota = (float) $this->getEffectiveLeaveQuota();

        // Hitung sisa libur bulan lalu
        $prevDate = \Carbon\Carbon::create($year, $month, 1)->subMonth();
        $sisaBulanLalu = $this->calculateRemainingLeaveForMonth($prevDate->year, $prevDate->month);

        return round($defaultQuota + max(0, $sisaBulanLalu), 1);
    }

    /**
     * Hitung sisa libur untuk bulan tertentu.
     */
    public function calculateRemainingLeaveForMonth(int $year, int $month): float
    {
        if ($this->join_date && $this->join_date->startOfMonth()->gt(\Carbon\Carbon::create($year, $month, 1))) {
            return 0.0;
        }

        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $dailies = \App\Models\DailyAttendance::where('employee_id', $this->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('keterangan', 'libur')
            ->get();

        if ($dailies->isEmpty()) {
            // Belum ada data absensi di bulan lalu, anggap sisa 0 atau default jika sudah lewat
            return 0.0;
        }

        $permits = \App\Models\Permit::where('employee_id', $this->id)
            ->whereBetween('permit_date', [$start->toDateString(), $end->toDateString()])
            ->where('type', \App\Models\Permit::TYPE_LIBUR)
            ->get()
            ->keyBy(fn ($p) => $p->permit_date->toDateString());

        $totalLiburUsed = 0.0;
        foreach ($dailies as $d) {
            $dateStr = $d->date instanceof \DateTimeInterface ? $d->date->format('Y-m-d') : substr((string) $d->date, 0, 10);
            $p = $permits->get($dateStr);
            if ($p && $p->leave_duration === 'setengah_hari') {
                $totalLiburUsed += 0.5;
            } else {
                $totalLiburUsed += 1.0;
            }
        }

        $defaultQuota = (float) $this->getEffectiveLeaveQuota();
        return max(0.0, round($defaultQuota - $totalLiburUsed, 1));
    }
}

