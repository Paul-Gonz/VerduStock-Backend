<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cabecera: ¿A quién y cuándo le compramos?
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->decimal('total_compra', 12, 2);
            $table->timestamp('fecha_compra')->useCurrent();
            $table->timestamps();
        });

        // Detalle: ¿Qué compramos y a qué precio?
        Schema::create('detalle_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos');
            $table->decimal('cantidad', 8, 3);
            $table->decimal('precio_costo', 10, 2); // Lo que te costó a ti
            $table->decimal('precio_venta_sugerido', 10, 2); // A cómo lo vas a vender
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_compras');
        Schema::dropIfExists('compras');
    }
};