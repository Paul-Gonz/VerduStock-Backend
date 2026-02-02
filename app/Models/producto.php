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
        'kilo',
        'detalle',
        'precio_compra',
        'precio_venta_kg',
        'proveedor_id',
        'desperdicio',
        'usuario_id'
    ];

    protected $casts = [
        'kilo' => 'decimal:3',
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
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Accesor: Kilogramos netos (descontando desperdicio)
     */
    public function getKilogramosNetosAttribute(): float
    {
        return (float) bcsub($this->kilo, $this->desperdicio, 3);
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

    public function getKilogramosAttribute($value)
    {
        if (!is_null($value)) {
            return (float) $value;
        }

        if (array_key_exists('kilo', $this->attributes)) {
            return (float) $this->attributes['kilo'];
        }

        return 0.0;
    }

    public function setKilogramosAttribute($value)
    {
        $this->attributes['kilo'] = (float) $value;
    }
}
