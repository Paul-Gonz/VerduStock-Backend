<?php
// app/Models/Producto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';
    
    protected $fillable = [
        'nombre',
        'categoria_id',
        'kilogramos', // Cambiar a 'kilonumeric' si ese es el nombre en BD
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

    /**
     * Relación con la categoría - SINGULAR (Categoria.php)
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Relación con el proveedor - PLURAL (Proveedores.php)
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedores::class, 'proveedor_id');
    }

    /**
     * Relación con el usuario - PLURAL (Usuarios.php)
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuarios::class, 'usuario_id');
    }

    /**
     * Accesor: Kilogramos netos (descontando desperdicio)
     */
    public function getKilogramosNetosAttribute(): float
    {
        return (float) bcsub($this->kilogramos, $this->desperdicio, 3);
    }

    /**
     * Accesor: Precio de venta total
     */
    public function getPrecioVentaTotalAttribute(): float
    {
        $kgNetos = $this->kilogramos_netos;
        return (float) bcmul($kgNetos, $this->precio_venta_kg, 2);
    }

    /**
     * Accesor: Margen de ganancia porcentual
     */
    public function getMargenGananciaAttribute(): float
    {
        if ($this->precio_compra > 0) {
            $ganancia = bcsub($this->precio_venta_total, $this->precio_compra, 2);
            return (float) bcmul(bcdiv($ganancia, $this->precio_compra, 4), 100, 2);
        }
        return 0.0;
    }

    /**
     * Accesor: Ganancia potencial
     */
    public function getGananciaPotencialAttribute(): float
    {
        return (float) bcsub($this->precio_venta_total, $this->precio_compra, 2);
    }

    /**
     * Scope: Buscar por nombre
     */
    public function scopePorNombre($query, $nombre)
    {
        return $query->where('nombre', 'LIKE', "%{$nombre}%");
    }

    /**
     * Scope: Por categoría
     */
    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }

    /**
     * Scope: Por proveedor
     */
    public function scopePorProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    /**
     * Scope: Por rango de fechas
     */
    public function scopePorRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }

    /**
     * IMPORTANTE: Si en BD el campo se llama 'kilonumeric' no 'kilogramos'
     */
    public function getKilogramosAttribute()
    {
        // Si existe campo 'kilonumeric' en BD, usarlo
        if (isset($this->attributes['kilonumeric'])) {
            return (float) $this->attributes['kilonumeric'];
        }
        // Si no, usar 'kilogramos'
        return (float) ($this->attributes['kilogramos'] ?? 0);
    }

    public function setKilogramosAttribute($value)
    {
        // Si existe campo 'kilonumeric' en BD, guardar ahí
        if (isset($this->attributes['kilonumeric'])) {
            $this->attributes['kilonumeric'] = (float) $value;
        } else {
            $this->attributes['kilogramos'] = (float) $value;
        }
    }
}
