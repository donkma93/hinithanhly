<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignment_notes', function (Blueprint $table) {
            $table->string('responsible_name')->nullable()->after('supplier_id');
        });

        DB::statement('ALTER TABLE consignment_notes MODIFY responsible_user_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE consignment_notes MODIFY responsible_user_id BIGINT UNSIGNED NOT NULL');

        Schema::table('consignment_notes', function (Blueprint $table) {
            $table->dropColumn('responsible_name');
        });
    }
};