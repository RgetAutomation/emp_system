<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('gst_no')->nullable()->after('address');
            $table->string('trade_license')->nullable()->after('gst_no');
            $table->string('logo')->nullable()->after('trade_license');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['gst_no', 'trade_license', 'logo']);
        });
    }
};
