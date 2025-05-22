<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('base_currency_code', 3);
            $table->string('target_currency_code', 3);
            $table->decimal('rate', 15, 6);
            $table->date('rate_date');
            $table->timestamp('created_at')->useCurrent()->nullable();

            $table->index('base_currency_code');
            $table->index('target_currency_code');
            $table->index('rate_date');
            $table->unique(['base_currency_code', 'target_currency_code', 'rate_date'], 'exchange_rates_unique_rate_for_date');

            $table->foreign('base_currency_code')->references('code')->on('currencies')->onDelete('cascade');
            $table->foreign('target_currency_code')->references('code')->on('currencies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
