<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'suppliers', 'consignment_notes', 'products', 'users'] as $table) {
            DB::statement("update {$table} set public_id = lpad(cast(id as char), 6, '0')");
        }
    }

    public function down(): void
    {
        foreach (['categories', 'suppliers', 'consignment_notes', 'products', 'users'] as $table) {
            DB::statement("update {$table} set public_id = lpad(cast(id as char), 26, '0')");
        }
    }
};