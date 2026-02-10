<?php

namespace App\Repositories;

use App\Models\proveedores;
use Illuminate\Support\Collection;

class ProveedoresRepository
{
    protected $model;

    public function __construct(proveedores $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        // Solo devuelve proveedores que no han sido borrados lógicamente
        return $this->model->all();
    }   

    public function find($id): ?proveedores
    {
        return $this->model->find($id);
    }

    public function create(array $data): proveedores
    {
        return $this->model->create($data);
    }

    public function update($id, array $data): ?proveedores
    {
        $record = $this->model->find($id);
        if ($record) {
            $record->update($data);
            return $record;
        }
        return null;
    }

    /**
     * Soft Delete: Marca al proveedor como eliminado
     */
    public function delete($id): bool
    {
        $record = $this->model->find($id);
        return $record ? $record->delete() : false;
    }

    // --- MÉTODOS DE GESTIÓN DE PAPELERA ---

    /**
     * Obtener proveedores en la papelera
     */
    public function onlyTrashed(): Collection
    {
        return $this->model->onlyTrashed()->get();
    }

    /**
     * Restaurar un proveedor de la papelera
     */
    public function restore($id): bool
    {
        $record = $this->model->withTrashed()->find($id);
        return $record ? $record->restore() : false;
    }

    /**
     * Borrado definitivo de la base de datos
     */
    public function forceDelete($id): bool
    {
        $record = $this->model->withTrashed()->find($id);
        return $record ? $record->forceDelete() : false;
    }
}