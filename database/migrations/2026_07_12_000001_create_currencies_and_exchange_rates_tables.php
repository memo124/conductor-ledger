<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary();
            $table->string('name', 80);
            $table->string('name_es', 80)->nullable();
            $table->string('symbol', 8)->default('$');
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3)->default('USD');
            $table->string('target_currency', 3);
            $table->decimal('rate', 20, 10);
            $table->timestamp('source_date')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['base_currency', 'target_currency']);
            $table->foreign('target_currency')->references('code')->on('currencies')->cascadeOnUpdate();
        });

        Schema::create('exchange_rate_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20);
            $table->unsignedInteger('currencies_count')->default(0);
            $table->unsignedTinyInteger('api_calls_used')->default(0);
            $table->timestamp('source_date')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_syncs');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
