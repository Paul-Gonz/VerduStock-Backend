<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProveedoresResource;
use App\Http\Requests\Proveedores\ProveedoresCreateRequest;
use App\Http\Requests\Proveedores\ProveedoresUpdateRequest;
use App\Repositories\ProveedoresRepository; 

class ProveedoresController extends Controller
{
    protected $proveedorRepository;

    public function __construct(ProveedoresRepository $proveedorRepository)
    {
        $this->proveedorRepository = $proveedorRepository;
    }

    public function index()
    {
        // El repositorio filtrará automáticamente los proveedores activos
        $proveedores = $this->proveedorRepository->all();
        return ProveedoresResource::collection($proveedores);
    }

    /**
     * NUEVO: Listar proveedores en la papelera
     */
    public function trashed(): JsonResponse
    {
        $proveedores = $this->proveedorRepository->onlyTrashed();
        return response()->json([
            'success' => true,
            'data' => ProveedoresResource::collection($proveedores)
        ]);
    }

    public function store(ProveedoresCreateRequest $request)
    {
        $proveedor = $this->proveedorRepository->create($request->validated());
        return new ProveedoresResource($proveedor);
    }

    public function show($id)
    {
        $proveedor = $this->proveedorRepository->find($id);
        if (!$proveedor) {
            return response()->json(['message' => 'Proveedor no encontrado'], 404);
        }
        return new ProveedoresResource($proveedor);
    }

    public function update(ProveedoresUpdateRequest $request, $id)
    {
        $data = array_filter($request->validated(), function ($value) {
            return !is_null($value);
        });

        if (empty($data)) {
            return response()->json(['message' => 'No hay datos para actualizar'], 400);
        }

        $updatedProveedor = $this->proveedorRepository->update($id, $data);

        if (!$updatedProveedor) {
            return response()->json(['message' => 'Proveedor no encontrado'], 404);
        }

        return new ProveedoresResource($updatedProveedor);
    }

    /**
     * Soft Delete: Mover a la papelera
     */
    public function destroy($id): JsonResponse
    {
        $deleted = $this->proveedorRepository->delete($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado o ya eliminado'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Proveedor movido a la papelera exitosamente'
        ]);
    }

    /**
     * NUEVO: Restaurar un proveedor
     */
    public function restore($id): JsonResponse
    {
        $restored = $this->proveedorRepository->restore($id);
        
        if (!$restored) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo encontrar el proveedor en la papelera'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Proveedor restaurado exitosamente'
        ]);
    }

    /**
     * NUEVO: Eliminación permanente
     */
    public function forceDelete($id): JsonResponse
    {
        $deleted = $this->proveedorRepository->forceDelete($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar eliminar permanentemente el proveedor'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Proveedor eliminado definitivamente del sistema'
        ]);
    }
}