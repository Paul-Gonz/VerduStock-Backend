<?php
// app/Models/Categoria.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    // La tabla se llama 'categorias' (español, plural)
    protected $table = 'categorias';  // ✅ 'categorias' no 'categories'
    
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