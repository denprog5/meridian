<?php

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
        Schema::create('country_language', function (Blueprint $table) {
            $table->string('country_code', 2);
            $table->string('language_code', 2);
            $table->string('status')->nullable()->index();

            $table->foreign('country_code')->references('iso_alpha_2')->on('countries')->onDelete('cascade');
            $table->foreign('language_code')->references('code')->on('languages')->onDelete('cascade');

            $table->primary(['country_code', 'language_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('country_language');
    }
};
