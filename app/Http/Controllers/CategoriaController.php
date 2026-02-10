<?php
// app/Http/Controllers/CategoriaController.php

namespace App\Http\Controllers;

use App\Http\Requests\Categoria\CategoriaCreateRequest;
use App\Http\Requests\Categoria\CategoriaUpdateRequest;
use App\Http\Resources\CategoriaResource;
use App\Repositories\CategoriaRepository;
use Illuminate\Http\JsonResponse;

class CategoriaController extends Controller
{
    protected $categoriaRepository;

    public function __construct(CategoriaRepository $categoriaRepository)
    {
        $this->categoriaRepository = $categoriaRepository;
    }

    public function index(): JsonResponse
    {
        // El repositorio ya filtrará las categorías eliminadas automáticamente
        $categorias = $this->categoriaRepository->paginate();
        
        return response()->json([
            'success' => true,
            'data' => CategoriaResource::collection($categorias),
            'meta' => [
                'total' => $categorias->total(),
                'per_page' => $categorias->perPage(),
                'current_page' => $categorias->currentPage(),
                'last_page' => $categorias->lastPage()
            ]
        ]);
    }

    // --- NUEVO: Listar categorías eliminadas (Papelera) ---
    public function trashed(): JsonResponse
    {
        $categorias = $this->categoriaRepository->onlyTrashed();
        
        return response()->json([
            'success' => true,
            'data' => CategoriaResource::collection($categorias)
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $categoria = $this->categoriaRepository->find($id);
        
        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada.'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => new CategoriaResource($categoria)
        ]);
    }

    public function store(CategoriaCreateRequest $request): JsonResponse
    {
        $categoria = $this->categoriaRepository->create($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Categoría creada exitosamente.',
            'data' => new CategoriaResource($categoria)
        ], 201);
    }

    public function update(CategoriaUpdateRequest $request, int $id): JsonResponse
    {
        $updated = $this->categoriaRepository->update($id, $request->validated());
        
        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada.'
            ], 404);
        }
        
        $categoria = $this->categoriaRepository->find($id);
        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada exitosamente.',
            'data' => new CategoriaResource($categoria)
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        // Al usar SoftDeletes en el modelo, este delete() solo llenará deleted_at
        $deleted = $this->categoriaRepository->delete($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada.'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Categoría movida a la papelera exitosamente.'
        ]);
    }

    // --- NUEVO: Restaurar una categoría ---
    public function restore(int $id): JsonResponse
    {
        $restored = $this->categoriaRepository->restore($id);
        
        if (!$restored) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo encontrar la categoría en la papelera.'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Categoría restaurada exitosamente.'
        ]);
    }

    // --- NUEVO: Borrado físico/permanente ---
    public function forceDelete(int $id): JsonResponse
    {
        $deleted = $this->categoriaRepository->forceDelete($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada.'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada permanentemente del sistema.'
        ]);
    }
}