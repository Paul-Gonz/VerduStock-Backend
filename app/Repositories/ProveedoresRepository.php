<?php

namespace App\Repositories;

use App\Models\proveedores;

class ProveedoresRepository
{
    protected $model;

    public function __construct(proveedores $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }   

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->model->find($id);
        if ($record) {
            $record->update($data);
            return $record;
        }
        return null;
    }

    public function delete($id)
    {
        // Agregar lógica de eliminación si es necesaria
    }
}