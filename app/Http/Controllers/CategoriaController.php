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
        $deleted = $this->categoriaRepository->delete($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada.'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente.'
        ]);
    }
}