<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    // Autenticación vía campo 'nombre'
    public function username()
    {
        return 'nombre';
    }

    protected $fillable = [
        'nombre',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    // Para compatibilidad con reset password
    public function getEmailForPasswordReset()
    {
        return $this->nombre;
    }

    // Para notificaciones
    public function routeNotificationFor($driver, $notification = null)
    {
        return $this->nombre;
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'usuario_id');
    }

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre'] = trim($value);
    }
}