<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');                    // e.g. "Eid ul-Fitr", "Independence Day"
            $table->date('date');                      // specific date
            $table->string('type')->default('national'); // national | company | optional
            $table->boolean('is_recurring')->default(false); // repeat every year on same month+day
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_holidays');
    }
};
