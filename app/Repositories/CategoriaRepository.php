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
        return $categoria ? $categoria->delete() : false;
    }

    // Método específico para buscar por nombre (varchar(100))
    public function findByNombre(string $nombre): ?Categoria
    {
        return $this->model->where('nombre', $nombre)->first();
    }

    // Método para verificar si existe por nombre
    public function existsByNombre(string $nombre, ?int $exceptId = null): bool
    {
        $query = $this->model->where('nombre', $nombre);
        
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
        
        return $query->exists();
    }
}