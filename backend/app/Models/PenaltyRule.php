<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenaltyRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'deduction_type',
        'deduction_value',
        'grace_minutes',
        'applies_after',
        'active',
    ];

    protected $casts = [
        'deduction_value' => 'decimal:2',
        'grace_minutes'   => 'integer',
        'applies_after'   => 'integer',
        'active'          => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
