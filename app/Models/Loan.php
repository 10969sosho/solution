<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'employee_id',
        'loan_date',
        'principal',
        'description',
        'status',
        'previous_loans_total',
        'all_loans_total',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'principal' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(0, (float) $this->principal - $this->total_paid);
    }

    public function isFullyPaid(): bool
    {
        return $this->remaining_balance <= 0;
    }
}