<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->timestamp('returned_at')->nullable()->after('description');
            $table->foreignId('returned_by_id')->nullable()->after('returned_at')->constrained('users')->nullOnDelete();
            $table->index('returned_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('returned_by_id');
            $table->dropIndex(['returned_at']);
            $table->dropColumn('returned_at');
        });
    }
};
