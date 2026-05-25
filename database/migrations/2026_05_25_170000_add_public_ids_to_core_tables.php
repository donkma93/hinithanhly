<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('public_id', 26)->nullable()->unique()->after('id');
            $table->index('created_at');
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->string('public_id', 26)->nullable()->unique()->after('id');
            $table->index('created_at');
        });

        Schema::table('consignment_notes', function (Blueprint $table): void {
            $table->string('public_id', 26)->nullable()->unique()->after('id');
            $table->index('created_at');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->string('public_id', 26)->nullable()->unique()->after('id');
            $table->index('created_at');
        });

        $this->backfillPublicIds('categories');
        $this->backfillPublicIds('suppliers');
        $this->backfillPublicIds('consignment_notes');
        $this->backfillPublicIds('products');
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });

        Schema::table('consignment_notes', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }

    private function backfillPublicIds(string $table): void
    {
        DB::table($table)
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['public_id' => str_pad((string) $row->id, 6, '0', STR_PAD_LEFT)]);
                }
            });
    }
};