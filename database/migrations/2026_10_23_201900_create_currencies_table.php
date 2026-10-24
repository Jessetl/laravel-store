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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id()->comment('Identificador único de la divisa');
            $table->string('name')->comment('Nombre de la divisa');
            $table->string('symbol')->comment('Símbolo de la divisa');
            $table->decimal('exchange_rate', 18, 6)->comment('Tasa de cambio de la divisa'); // tasas tipo 0.00004321
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
