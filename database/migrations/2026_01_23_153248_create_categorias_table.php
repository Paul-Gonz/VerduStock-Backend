<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->bigIncrements('id');  // bigserial
            $table->string('nombre', 100); // character varying(100)
            $table->text('detalle')->nullable(); // text
            $table->string('emoji', 16)->nullable(); // character varying(16)
            $table->timestampsTz(); // timestamp with time zone (created_at, updated_at)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};