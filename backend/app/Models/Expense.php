<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'title',
        'description',
        'amount',
        'date_incurred',
        'category',
        'receipt_path',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'date_incurred' => 'date',
        'amount' => 'decimal:2',
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
