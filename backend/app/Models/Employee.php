<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'department_id',
        'designation_id',
        'phone',
        'address',
        'salary',
        'join_date',
        'employee_id',
        'gender',
        'dob',
        'employment_type',
        'status',
        'personal_details',
        'bank_details',
        'identity_docs',
        'education_experience',
        'documents'
    ];

    protected $casts = [
        'personal_details' => 'array',
        'bank_details' => 'array',
        'identity_docs' => 'array',
        'education_experience' => 'array',
        'documents' => 'array',
        'join_date' => 'date',
        'dob' => 'date',
        'salary' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }
}
