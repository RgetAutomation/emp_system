<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'month',
        'basic_salary',
        'gross_salary',
        'overtime_pay',
        'pf_deduction',
        'esi_deduction',
        'pt_deduction',
        'tds_deduction',
        'other_deductions',
        'net_salary',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
