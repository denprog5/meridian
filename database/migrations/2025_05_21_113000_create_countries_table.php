<?php

declare(strict_types=1);

use Denprog\Meridian\Enums\Continent;
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
        Schema::create('countries', function (Blueprint $table): void {
            $table->id();
            $table->char('continent_code', 2)->default(Continent::NORTH_AMERICA->value)->index();
            $table->string('name')->index();
            $table->string('official_name')->nullable();
            $table->string('native_name')->nullable();
            $table->char('iso_alpha_2', 2)->unique()->comment('ISO 3166-1 alpha-2 code');
            $table->char('iso_alpha_3', 3)->unique()->comment('ISO 3166-1 alpha-3 code');
            $table->char('iso_numeric', 3)->unique()->nullable()->comment('ISO 3166-1 numeric code');
            $table->string('phone_code')->nullable()->comment('International phone calling code(s), comma-separated if multiple');
            $table->string('currency_code', 3)->nullable();
            $table->timestamps();

            $table->foreign('currency_code')
                ->references('code')
                ->on('currencies')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
