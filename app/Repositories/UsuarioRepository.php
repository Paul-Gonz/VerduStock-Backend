<?php

namespace App\Repositories;

// 1. Cambiamos el import para usar el modelo User
use App\Models\User; 
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsuarioRepository
{
    protected $model;

    // 2. Cambiamos el TypeHint en el constructor
    public function __construct(User $usuario) 
    {
        $this->model = $usuario;
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function findByNombre($nombre)
    {
        return $this->model->where('nombre', $nombre)->first();
    }

    public function create(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $usuario = $this->find($id);
        if (!$usuario) {
            return null;
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $usuario->update($data);
        return $usuario;
    }

    public function delete($id)
    {
        $usuario = $this->find($id);
        if (!$usuario) {
            return false;
        }

        return $usuario->delete();
    }

    public function all()
    {
        return $this->model->all();
    }

    public function paginate($perPage = 15)
    {
        return $this->model->paginate($perPage);
    }

    public function checkNombreExists($nombre, $excludeId = null)
    {
        $query = $this->model->where('nombre', trim($nombre));
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}