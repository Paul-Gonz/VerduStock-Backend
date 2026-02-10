<?php
// app/Repositories/CategoriaRepository.php

namespace App\Repositories;

use App\Models\Categoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CategoriaRepository
{
    protected $model;

    public function __construct(Categoria $categoria)
    {
        $this->model = $categoria;
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function find(int $id): ?Categoria
    {
        return $this->model->find($id);
    }

    public function create(array $data): Categoria
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $categoria = $this->find($id);
        return $categoria ? $categoria->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $categoria = $this->find($id);
        // Al usar SoftDeletes en el modelo, esto solo llenará 'deleted_at'
        return $categoria ? $categoria->delete() : false;
    }

    // --- NUEVOS MÉTODOS PARA SOFT DELETE ---

    /**
     * Obtener solo los registros eliminados (Papelera)
     */
    public function onlyTrashed(): Collection
    {
        return $this->model->onlyTrashed()->get();
    }

    /**
     * Restaurar una categoría eliminada
     */
    public function restore(int $id): bool
    {
        $categoria = $this->model->withTrashed()->find($id);
        return $categoria ? $categoria->restore() : false;
    }

    /**
     * Eliminar permanentemente de la base de datos
     */
    public function forceDelete(int $id): bool
    {
        $categoria = $this->model->withTrashed()->find($id);
        return $categoria ? $categoria->forceDelete() : false;
    }

    // --- MÉTODOS DE BÚSQUEDA ---

    public function findByNombre(string $nombre): ?Categoria
    {
        return $this->model->where('nombre', $nombre)->first();
    }

    public function existsByNombre(string $nombre, ?int $exceptId = null): bool
    {
        $query = $this->model->where('nombre', $nombre);
        
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
        
        return $query->exists();
    }
}