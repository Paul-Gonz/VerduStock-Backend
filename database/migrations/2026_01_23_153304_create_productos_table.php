<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            
            // Relación con categorias
            $table->foreignId('categoria_id')
                  ->constrained('categorias')
                  ->onDelete('restrict');
            
            $table->string('nombre', 150);
            $table->decimal('kilo', 8, 3); 
            $table->decimal('precio_compra', 10, 2); 
            $table->decimal('precio_ventakg', 10, 2); 
            
            // Relación con proveedores
            $table->foreignId('proveedor_id')
                  ->constrained('proveedores')
                  ->onDelete('restrict');
            
            $table->decimal('desperdicio', 8, 3)->default(0); 
            
            // Relación con usuarios 
            $table->foreignId('usuario_id')
                  ->constrained('usuarios')
                  ->onDelete('cascade');
            
            $table->timestamps();
            
            // Índices
            $table->index('categoria_id');
            $table->index('proveedor_id');
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};