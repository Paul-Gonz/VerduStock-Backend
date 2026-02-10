<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory;
    use SoftDeletes; 
    
    protected $dates = ['deleted_at'];
    
    protected $table = 'productos';
    
    protected $fillable = [
        'nombre',
        'categoria_id',
        'kilogramos', 
        'detalle',
        'precio_compra',
        'precio_venta_kg', 
        'proveedor_id',
        'desperdicio',
        'usuario_id'
    ];

    protected $casts = [
        'kilogramos' => 'decimal:3',
        'precio_compra' => 'decimal:2',
        'precio_venta_kg' => 'decimal:2',
        'desperdicio' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedores::class, 'proveedor_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function getKilogramosNetosAttribute(): float
    {
        return (float) bcsub($this->kilogramos, $this->desperdicio, 3);
    }

    public function getPrecioVentaTotalAttribute(): float
    {
        return (float) bcmul($this->kilogramos_netos, $this->precio_venta_kg, 2);
    }

    public function getGananciaPotencialAttribute(): float
    {
        return (float) bcsub($this->precio_venta_total, $this->precio_compra, 2);
    }

    public function getKilogramosAttribute($value)
    {
        return (float) ($value ?? 0.0);
    }

    public function setKilogramosAttribute($value)
    {
        $this->attributes['kilogramos'] = (float) $value;
    }

    public function getPrecioVentaKgAttribute($value)
    {
        return (float) ($value ?? 0.0);
    }

    public function setPrecioVentaKgAttribute($value)
    {
        $this->attributes['precio_venta_kg'] = (float) $value;
    }
}