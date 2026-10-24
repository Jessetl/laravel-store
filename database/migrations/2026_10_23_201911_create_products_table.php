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
        Schema::create('products', function (Blueprint $table) {
            $table->id()->comment('Identificador único del producto');
            $table->string('name')->comment('Nombre del producto');
            $table->text('description')->comment('Descripción del producto');
            $table->decimal('price', 12, 2)->comment('Precio del producto en la divisa base');
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('tax_cost', 12, 2)->comment('Costo del impuesto aplicado al producto');
            $table->decimal('manufacturing_cost', 12, 2)->comment('Costo de fabricación del producto');
            $table->timestamps();
            $table->softDeletes()->comment('Fecha de eliminación del producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
