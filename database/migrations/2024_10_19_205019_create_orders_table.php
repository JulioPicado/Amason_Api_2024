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
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id'); // Auto-incremental
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relación con la tabla de usuarios
            $table->unsignedBigInteger('payment_method_id')->nullable(); // Columna para la relación con métodos de pago
            $table->foreign('payment_method_id')->references('id')->on('payment_method')->onDelete('cascade');
            $table->double('total_amount'); // Monto total
            $table->integer('status'); // Estado de la orden
            $table->timestamps(); // Campos created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
