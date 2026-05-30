<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method', 20);
            $table->string('payment_reference')->nullable()->index();
            $table->decimal('total_amount', 14, 2);
            $table->unsignedInteger('items_count');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index('completed_at');
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->nullable()->unique();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_public_id', 26);
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 14, 2);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
            $table->index('product_public_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};