<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'name',
        'basic_salary',
        'components',
        'effective_from',
        'is_active',
    ];

    protected $casts = [
        'basic_salary'   => 'decimal:2',
        'components'     => 'array',
        'effective_from' => 'date',
        'is_active'      => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Calculate the gross salary (basic + allowances - deductions) from components.
     */
    public function calculateGross(): float
    {
        $gross = (float) $this->basic_salary;
        $components = $this->components ?? [];

        foreach ($components as $component) {
            $amount = (float) ($component['amount'] ?? 0);
            $isPercent = (bool) ($component['is_percentage'] ?? false);
            $value = $isPercent ? ($this->basic_salary * $amount / 100) : $amount;

            if (($component['type'] ?? '') === 'allowance') {
                $gross += $value;
            } elseif (($component['type'] ?? '') === 'deduction') {
                $gross -= $value;
            }
        }

        return max(0, $gross);
    }
}
