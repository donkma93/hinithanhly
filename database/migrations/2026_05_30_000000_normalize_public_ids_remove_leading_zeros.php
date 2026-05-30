<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'categories', 'suppliers', 'consignment_notes', 'products'] as $table) {
            DB::table($table)
                ->where('public_id', 'like', '0%')
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($table): void {
                    foreach ($rows as $row) {
                        $normalized = ltrim((string) $row->public_id, '0');

                        DB::table($table)
                            ->where('id', $row->id)
                            ->update([
                                'public_id' => $normalized !== '' ? $normalized : '0',
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        // Public IDs are derived from the numeric primary key, so the original zero-padded form cannot be
        // reconstructed reliably for all historical records.
    }
};