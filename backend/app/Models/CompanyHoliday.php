<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyHoliday extends Model
{
    use HasFactory;

    protected $table = 'company_holidays';

    protected $fillable = [
        'company_id',
        'name',
        'date',
        'type',
        'is_recurring',
        'description',
    ];

    protected $casts = [
        'date'         => 'date',
        'is_recurring' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
