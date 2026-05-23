<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('financial_year'); // e.g., 2024-2025
            $table->enum('regime', ['old', 'new'])->default('new');
            $table->decimal('rent_paid', 10, 2)->default(0); // For HRA
            $table->decimal('section_80c', 10, 2)->default(0); // Max 1.5L
            $table->decimal('section_80d', 10, 2)->default(0); // Medical
            $table->decimal('home_loan_interest', 10, 2)->default(0); // Section 24b
            $table->json('other_deductions')->nullable(); 
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'financial_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_declarations');
    }
};
