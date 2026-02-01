<?php
// app/Repositories/ProductoRepository.php

namespace App\Repositories;

use App\Models\Producto;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductoRepository
{
    protected $model;

    public function __construct(Producto $producto)
    {
        $this->model = $producto;
    }

    /**
     * Obtener todos los productos
     */
    public function all(array $with = []): Collection
    {
        return $this->model->with($this->getDefaultRelations($with))->get();
    }

    /**
     * Paginar productos
     */
    public function paginate(int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->model->with($this->getDefaultRelations($with))->paginate($perPage);
    }

    /**
     * Buscar producto por ID
     */
    public function find(int $id, array $with = []): ?Producto
    {
        return $this->model->with($this->getDefaultRelations($with))->find($id);
    }

    /**
     * Buscar producto o fallar
     */
    public function findOrFail(int $id, array $with = []): Producto
    {
        return $this->model->with($this->getDefaultRelations($with))->findOrFail($id);
    }

    /**
     * Crear nuevo producto
     */
    public function create(array $data): Producto
    {
        return $this->model->create($data);
    }

    /**
     * Actualizar producto
     */
    public function update(int $id, array $data): bool
    {
        $producto = $this->find($id);
        return $producto ? $producto->update($data) : false;
    }

    /**
     * Eliminar producto
     */
    public function delete(int $id): bool
    {
        $producto = $this->find($id);
        return $producto ? $producto->delete() : false;
    }

    /**
     * Buscar productos con filtros
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with($this->getDefaultRelations());

        // Búsqueda por texto
        if (!empty($filters['busqueda'])) {
            $busqueda = $filters['busqueda'];
            $query->where(function($q) use ($busqueda) {
                $q->where('nombre', 'LIKE', "%{$busqueda}%")
                  ->orWhere('detalle', 'LIKE', "%{$busqueda}%")
                  ->orWhereHas('categoria', function($q) use ($busqueda) {
                      $q->where('nombre', 'LIKE', "%{$busqueda}%");
                  })
                  ->orWhereHas('proveedor', function($q) use ($busqueda) {
                      $q->where('nombre', 'LIKE', "%{$busqueda}%");
                  });
            });
        }

        // Filtros
        if (!empty($filters['categoria_id'])) {
            $query->where('categoria_id', $filters['categoria_id']);
        }

        if (!empty($filters['proveedor_id'])) {
            $query->where('proveedor_id', $filters['proveedor_id']);
        }

        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
            $query->whereBetween('created_at', [
                $filters['fecha_inicio'],
                $filters['fecha_fin']
            ]);
        }

        // Ordenamiento
        $ordenCampo = $filters['orden_campo'] ?? 'created_at';
        $ordenDireccion = $filters['orden_direccion'] ?? 'desc';
        $query->orderBy($ordenCampo, $ordenDireccion);

        return $query->paginate($perPage);
    }

    /**
     * Obtener estadísticas
     */
    public function getEstadisticas(array $filters = []): array
    {
        $query = $this->model;

        // Aplicar filtros
        if (!empty($filters['categoria_id'])) {
            $query->where('categoria_id', $filters['categoria_id']);
        }

        if (!empty($filters['proveedor_id'])) {
            $query->where('proveedor_id', $filters['proveedor_id']);
        }

        if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
            $query->whereBetween('created_at', [
                $filters['fecha_inicio'],
                $filters['fecha_fin']
            ]);
        }

        $productos = $query->get();

        return [
            'total_productos' => $productos->count(),
            'total_inversion' => (float) $productos->sum('precio_compra'),
            'total_venta_potencial' => (float) $productos->sum('precio_venta_total'),
            'total_ganancia_potencial' => (float) $productos->sum('ganancia_potencial'),
            'total_desperdicio_kg' => (float) $productos->sum('desperdicio'),
            'promedio_margen' => (float) $productos->avg('margen_ganancia'),
            'producto_mayor_inversion' => $productos->sortByDesc('precio_compra')->first(),
            'producto_mayor_margen' => $productos->sortByDesc('margen_ganancia')->first()
        ];
    }

    /**
     * Obtener productos por categoría
     */
    public function getPorCategoria(int $categoriaId): Collection
    {
        return $this->model->where('categoria_id', $categoriaId)
            ->with(['categoria', 'proveedor'])
            ->get();
    }

    /**
     * Verificar si existe producto con nombre
     */
    public function existsByNombre(string $nombre, ?int $exceptId = null): bool
    {
        $query = $this->model->where('nombre', $nombre);
        
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
        
        return $query->exists();
    }

    /**
     * Obtener productos con alto desperdicio
     */
    public function getConAltoDesperdicio(float $porcentajeUmbral = 0.3): Collection
    {
        return $this->model->whereRaw('desperdicio / kilogramos > ?', [$porcentajeUmbral])
            ->with(['categoria', 'proveedor'])
            ->get();
    }

    /**
     * Obtener relaciones por defecto
     */
    private function getDefaultRelations(array $additional = []): array
    {
        $default = ['categoria', 'proveedor', 'usuario'];
        return array_merge($default, $additional);
    }
}