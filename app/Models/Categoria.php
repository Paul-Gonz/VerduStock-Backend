<?php
// app/Models/Categoria.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    // La tabla se llama 'categorias' (español, plural)
    protected $table = 'categorias';  // ✅ 'categorias' no 'categories'

    use SoftDeletes; // Agrega esta línea para habilitar soft deletes
    protected $dates = ['deleted_at']; // Asegúrate de que 'deleted_at' sea tratado como una fecha
    
    protected $fillable = [
        'nombre',
        'detalle',
        'emoji',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}