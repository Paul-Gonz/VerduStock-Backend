<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Asegúrate de tener esto para la API

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Adaptamos los campos a tu lógica de VerduStock
     */
    protected $fillable = [
        'nombre',    // Cambiado de 'name'
        'password',
        'rol',       // Imagino que tienes un rol
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
        ];
    }

    /**
     * IMPORTANTE: Esto le dice a Laravel que use 'nombre' 
     * en lugar de 'email' para la autenticación.
     */
    public function getAuthIdentifierName()
    {
        return 'nombre';
    }
}