<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'financial_year',
        'regime',
        'rent_paid',
        'section_80c',
        'section_80d',
        'home_loan_interest',
        'other_deductions',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'other_deductions' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
