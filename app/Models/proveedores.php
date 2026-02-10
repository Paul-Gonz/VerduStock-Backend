<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class proveedores extends Model
{
    use SoftDeletes;

    protected $table = 'proveedores';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'telefono',
        'direccion',
        'detalle',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'nombre' => 'string',
        'telefono' => 'string',
        'direccion' => 'string',
        'detalle' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
