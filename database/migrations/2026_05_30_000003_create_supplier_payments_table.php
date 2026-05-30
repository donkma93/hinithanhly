<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->nullable()->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_reference')->nullable()->unique();
            $table->date('period_from');
            $table->date('period_to');
            $table->decimal('gross_amount', 14, 2);
            $table->decimal('discount_rate', 8, 2);
            $table->decimal('discount_amount', 14, 2);
            $table->decimal('payable_amount', 14, 2);
            $table->string('bank_name');
            $table->string('bank_account_name');
            $table->string('bank_account_number');
            $table->text('qr_url');
            $table->text('payload');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};