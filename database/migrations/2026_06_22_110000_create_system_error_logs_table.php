<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_error_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('error_uuid')->unique();
            $table->string('exception_class');
            $table->text('message')->nullable();
            $table->text('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('method', 20)->nullable();
            $table->text('url')->nullable();
            $table->string('route_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('trace')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_error_logs');
    }
};
