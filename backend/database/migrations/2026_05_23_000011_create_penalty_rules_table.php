<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('penalty_rules')) {
            Schema::create('penalty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['late', 'absent', 'half_day']);
            $table->enum('deduction_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('deduction_value', 10, 2)->default(0);
            $table->integer('grace_minutes')->default(0); // grace period for late
            $table->integer('applies_after')->default(1); // apply penalty after N occurrences
            $table->boolean('active')->default(true);
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penalty_rules');
    }
};
