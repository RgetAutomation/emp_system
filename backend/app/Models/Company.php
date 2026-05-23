<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'domain',
        'phone',
        'address',
        'status',
        'subscription_ends_at',
        'gst_no',
        'trade_license',
        'logo',
        'emp_id_prefix',
        'emp_id_padding',
        'settings',
    ];

    protected $casts = [
        'subscription_ends_at' => 'date',
        'emp_id_padding' => 'integer',
        'settings' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function designations()
    {
        return $this->hasMany(Designation::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
