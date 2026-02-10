<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductoResource;
use App\Repositories\ProductoRepository;
use App\Models\Categoria;
use App\Models\Proveedores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ProductoController extends Controller
{
    protected $productoRepository;

    public function __construct(ProductoRepository $productoRepository)
    {
        $this->productoRepository = $productoRepository;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['busqueda', 'categoria_id', 'proveedor_id', 'orden_campo', 'orden_direccion']);
        $filters['por_pagina'] = $request->get('por_pagina', 10);
        
        $productos = $this->productoRepository->search($filters);
        
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => ProductoResource::collection($productos),
                'meta' => [
                    'total' => $productos->total(),
                    'current_page' => $productos->currentPage(),
                    'last_page' => $productos->lastPage()
                ]
            ]);
        }
        
        return view('productos.index', compact('productos'));
    }

    // 🔹 NUEVO: Método para el Dashboard (Estadísticas)
    public function reporte(Request $request)
    {
        $estadisticas = $this->productoRepository->getEstadisticas($request->all());
        
        return response()->json([
            'success' => true,
            'data' => $estadisticas
        ]);
    }

    // 🔹 NUEVO: Mostrar un producto
    public function show(Request $request, $id)
    {
        $producto = $this->productoRepository->find($id);
        if (!$producto) return $this->handleResponse($request, 'No encontrado', false, 404);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => new ProductoResource($producto)]);
        }
        return view('productos.show', compact('producto'));
    }

    // 🔹 NUEVO: Actualizar producto
    public function update(Request $request, $id)
    {
        $validated = $this->validateProducto($request);
        $actualizado = $this->productoRepository->update($id, $validated);

        if (!$actualizado) return $this->handleResponse($request, 'No se pudo actualizar', false, 400);

        return $this->handleResponse($request, 'Producto actualizado correctamente');
    }

    // 🔹 NUEVO: Productos por categoría
    public function porCategoria($categoriaId)
    {
        $productos = $this->productoRepository->getPorCategoria($categoriaId);
        return response()->json(['success' => true, 'data' => ProductoResource::collection($productos)]);
    }

    // 🔹 NUEVO: Alto desperdicio
    public function altoDesperdicio()
    {
        $productos = $this->productoRepository->getConAltoDesperdicio();
        return response()->json(['success' => true, 'data' => ProductoResource::collection($productos)]);
    }

    /** Papelera y Restauración **/

    public function trashed(Request $request)
    {
        $productos = $this->productoRepository->onlyTrashed();
        return response()->json(['success' => true, 'data' => ProductoResource::collection($productos)]);
    }

    public function restore(Request $request, $id)
    {
        $restored = $this->productoRepository->restore($id);
        return $this->handleResponse($request, $restored ? 'Restaurado' : 'No encontrado', $restored);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validated = $this->validateProducto($request);
            $validated['usuario_id'] = auth()->id();

            $producto = $this->productoRepository->create($validated);
            DB::commit();

            return response()->json(['success' => true, 'data' => new ProductoResource($producto)], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $eliminado = $this->productoRepository->delete($id);
        return $this->handleResponse($request, $eliminado ? 'A papelera' : 'Error', $eliminado);
    }

    public function forceDelete(Request $request, $id)
    {
        $eliminado = $this->productoRepository->forceDelete($id);
        return $this->handleResponse($request, $eliminado ? 'Eliminado permanente' : 'Error', $eliminado);
    }

    // Helper para respuestas consistentes
    private function handleResponse(Request $request, $message, $success = true, $code = 200)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => $success, 'message' => $message], $code);
        }
        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }

    private function validateProducto(Request $request)
    {
        return $request->validate([
            'nombre' => 'required|string|max:100',
            'categoria_id' => 'required|exists:categorias,id',
            'kilogramos' => 'required|numeric|min:0.001',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta_kg' => 'required|numeric|min:0',
            'proveedor_id' => 'required|exists:proveedores,id',
            'desperdicio' => 'nullable|numeric|min:0|lt:kilogramos',
            'detalle' => 'nullable|string|max:500'
        ]);
    }
}