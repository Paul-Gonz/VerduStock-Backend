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
            $table->decimal('kilogramos', 8, 3);
            $table->text('detalle')->nullable();
            $table->decimal('precio_compra', 10, 2);
            $table->decimal('precio_venta_kg', 10, 2);
            $table->foreignId('proveedor_id')
                  ->constrained('proveedores')
                  ->onDelete('restrict');
            $table->decimal('desperdicio', 8, 3)->default(0);
            $table->foreignId('usuario_id')
                  ->constrained('usuarios')
                  ->onDelete('restrict');
            $table->timestamps();
            
            // Índices
            $table->index('nombre');
            $table->index('categoria_id');
            $table->index('proveedor_id');
            $table->index('usuario_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};