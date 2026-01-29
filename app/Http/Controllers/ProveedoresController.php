<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\support\Facades\Auth;

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
        $proveedores = $this->proveedorRepository->all();
        return ProveedoresResource::collection($proveedores);
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

    public function destroy($id)
    {
        // Implementar lógica de eliminación si es necesaria
    }
}
