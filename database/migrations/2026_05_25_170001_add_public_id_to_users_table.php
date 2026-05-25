<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('public_id', 26)->nullable()->unique()->after('id');
            $table->index('created_at');
        });

        DB::table('users')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('users')
                        ->where('id', $row->id)
                        ->update(['public_id' => str_pad((string) $row->id, 6, '0', STR_PAD_LEFT)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};