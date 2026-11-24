<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->padPublicIds(6);
    }

    public function down(): void
    {
        $this->padPublicIds(26);
    }

    private function padPublicIds(int $length): void
    {
        foreach (['categories', 'suppliers', 'consignment_notes', 'products', 'users'] as $table) {
            DB::table($table)
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($table, $length): void {
                    foreach ($rows as $row) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update([
                                'public_id' => str_pad((string) $row->id, $length, '0', STR_PAD_LEFT),
                            ]);
                    }
                });
        }
    }
};