<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('consignment_notes', function (Blueprint $table): void {
            $table->softDeletes();
        });

        $permissionTable = config('permission.table_names.permissions');

        if (! empty($permissionTable)) {
            Schema::table($permissionTable, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('consignment_notes', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        $permissionTable = config('permission.table_names.permissions');

        if (! empty($permissionTable)) {
            Schema::table($permissionTable, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
