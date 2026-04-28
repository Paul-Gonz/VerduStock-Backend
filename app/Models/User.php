<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * IMPORTANTE: Apuntamos a la tabla real en Supabase
     */
    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'password',
        'rol',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'datetime:Y-m-d H:i:s', 
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];  
    }

    /**
     * Le dice a Laravel que el identificador es 'nombre' y no 'email'
     */
    public function getAuthIdentifierName()
    {
        return 'nombre';
    }

    /**
     * Mantenemos la relación con productos que tenías en el otro modelo
     */
    public function productos()
    {
        return $this->hasMany(Producto::class, 'usuario_id');
    }

    /**
     * Mutador para asegurar que el nombre siempre vaya limpio
     */
    public function setNombreAttribute($value)
    {
        $this->attributes['nombre'] = trim($value);
    }
}