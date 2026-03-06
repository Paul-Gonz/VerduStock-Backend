<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_productos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->foreignId('categoria_id')
                  ->constrained('categorias')
                  ->onDelete('restrict');
            $table->decimal('stock_actual', 8, 3);
            $table->text('detalle')->nullable();
            $table->foreignId('usuario_id')
                  ->constrained('usuarios')
                  ->onDelete('restrict');
            $table->decimal('stock_minimo', 8, 3)->default(0);
            $table->timestamps();
            
            // Índices
            $table->index('nombre');
            $table->index('usuario_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};