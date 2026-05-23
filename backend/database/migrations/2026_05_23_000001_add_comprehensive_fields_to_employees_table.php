<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->after('id');
            $table->string('gender')->nullable()->after('join_date');
            $table->date('dob')->nullable()->after('gender');
            $table->string('employment_type')->nullable()->after('dob');
            $table->string('status')->default('active')->after('employment_type');
            
            $table->json('personal_details')->nullable()->after('status');
            $table->json('bank_details')->nullable()->after('personal_details');
            $table->json('identity_docs')->nullable()->after('bank_details');
            $table->json('education_experience')->nullable()->after('identity_docs');
            $table->json('documents')->nullable()->after('education_experience');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};
