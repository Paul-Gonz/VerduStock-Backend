<?php
// app/Http/Controllers/ProductoController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Repositories\ProductoRepository;
use App\Models\Categoria;
use App\Models\Proveedores;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    protected $productoRepository;

    public function __construct(ProductoRepository $productoRepository)
    {
        $this->productoRepository = $productoRepository;
    }

    /**
     * Display a listing of the resource (WEB y API).
     */
    public function index(Request $request)
    {
        $filters = $request->only(['busqueda', 'categoria_id', 'proveedor_id', 'orden_campo', 'orden_direccion']);
        $filters['por_pagina'] = $request->get('por_pagina', 10);
        
        $productos = $this->productoRepository->search($filters);
        
        // Si es una petición JSON/API
        if ($request->expectsJson() || $request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Productos obtenidos exitosamente',
                'data' => ProductoResource::collection($productos),
                'meta' => [
                    'total' => $productos->total(),
                    'per_page' => $productos->perPage(),
                    'current_page' => $productos->currentPage(),
                    'last_page' => $productos->lastPage()
                ],
                'links' => [
                    'first' => $productos->url(1),
                    'last' => $productos->url($productos->lastPage()),
                    'prev' => $productos->previousPageUrl(),
                    'next' => $productos->nextPageUrl()
                ]
            ]);
        }
        
        // Si es una petición web (HTML)
        $categorias = Categoria::all();
        $proveedores = Proveedores::all();
        
        return view('productos.index', compact('productos', 'categorias', 'proveedores'));
    }

    /**
     * Show the form for creating a new resource (solo WEB).
     */
    public function create()
    {
        // Este método solo para web, no para API
        $categorias = Categoria::all();
        $proveedores = Proveedores::all();
        $usuarios = Usuario::all();
        
        return view('productos.create', compact('categorias', 'proveedores', 'usuarios'));
    }

    /**
     * Store a newly created resource in storage (WEB y API).
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Determinar si es JSON o FormData
            if ($request->isJson() || $request->expectsJson()) {
                // Para JSON requests - usar validación manual
                $validated = $request->validate([
                    'nombre' => 'required|string|max:100',
                    'categoria_id' => 'required|exists:categorias,id',
                    'kilogramos' => 'required|numeric|min:0.001|decimal:0,3',
                    'precio_compra' => 'required|numeric|min:0|decimal:0,2',
                    'precio_venta_kg' => 'required|numeric|min:0|decimal:0,2',
                    'proveedor_id' => 'required|exists:proveedores,id',
                    'desperdicio' => 'nullable|numeric|min:0|decimal:0,3',
                    'usuario_id' => 'required|exists:usuarios,id',
                    'detalle' => 'nullable|string|max:500'
                ]);
                
                // Validación adicional: desperdicio < kilogramos
                if (isset($validated['desperdicio']) && $validated['desperdicio'] >= $validated['kilogramos']) {
                    throw ValidationException::withMessages([
                        'desperdicio' => ['El desperdicio debe ser menor que los kilogramos totales']
                    ]);
                }
            } else {
                // Para FormData - usar el FormRequest
                $validated = $request->validate([
                    'nombre' => 'required|string|max:100',
                    'categoria_id' => 'required|exists:categorias,id',
                    'kilogramos' => 'required|numeric|min:0.001|decimal:0,3',
                    'precio_compra' => 'required|numeric|min:0|decimal:0,2',
                    'precio_venta_kg' => 'required|numeric|min:0|decimal:0,2',
                    'proveedor_id' => 'required|exists:proveedores,id',
                    'desperdicio' => 'nullable|numeric|min:0|decimal:0,3|lt:kilogramos',
                    'usuario_id' => 'required|exists:usuarios,id',
                    'detalle' => 'nullable|string|max:500'
                ]);
            }
            
            $producto = $this->productoRepository->create($validated);
            
            DB::commit();
            
            // Respuesta para JSON
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto creado exitosamente',
                    'data' => new ProductoResource($producto)
                ], 201);
            }
            
            // Respuesta para web
            return redirect()->route('productos.index')
                ->with('success', 'Producto creado exitosamente.');
                
        } catch (ValidationException $e) {
            // Manejo de errores de validación
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el producto: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Error al crear el producto: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource (WEB y API).
     */
    public function show(Request $request, $id)
    {
        try {
            $producto = $this->productoRepository->findOrFail($id);
            
            // Si es una petición JSON/API
            if ($request->expectsJson() || $request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto obtenido exitosamente',
                    'data' => new ProductoResource($producto)
                ]);
            }
            
            // Si es una petición web (HTML)
            return view('productos.show', compact('producto'));
            
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado: ' . $e->getMessage()
                ], 404);
            }
            
            return redirect()->route('productos.index')
                ->with('error', 'Producto no encontrado');
        }
    }

    /**
     * Show the form for editing the specified resource (solo WEB).
     */
    public function edit($id)
    {
        try {
            $producto = $this->productoRepository->findOrFail($id);
            $categorias = Categoria::all();
            $proveedores = Proveedores::all();
            $usuarios = Usuario::all();
            
            return view('productos.edit', compact('producto', 'categorias', 'proveedores', 'usuarios'));
            
        } catch (\Exception $e) {
            return redirect()->route('productos.index')
                ->with('error', 'Producto no encontrado');
        }
    }

    /**
     * Update the specified resource in storage (WEB y API).
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            // Determinar si es JSON o FormData
            if ($request->isJson() || $request->expectsJson()) {
                // Para JSON requests
                $validated = $request->validate([
                    'nombre' => 'sometimes|required|string|max:100',
                    'categoria_id' => 'sometimes|required|exists:categorias,id',
                    'kilogramos' => 'sometimes|required|numeric|min:0.001|decimal:0,3',
                    'precio_compra' => 'sometimes|required|numeric|min:0|decimal:0,2',
                    'precio_venta_kg' => 'sometimes|required|numeric|min:0|decimal:0,2',
                    'proveedor_id' => 'sometimes|required|exists:proveedores,id',
                    'desperdicio' => 'nullable|numeric|min:0|decimal:0,3',
                    'usuario_id' => 'sometimes|required|exists:usuarios,id',
                    'detalle' => 'nullable|string|max:500'
                ]);
                
                // Validación adicional: desperdicio < kilogramos
                if (isset($validated['desperdicio']) && isset($validated['kilogramos']) && 
                    $validated['desperdicio'] >= $validated['kilogramos']) {
                    throw ValidationException::withMessages([
                        'desperdicio' => ['El desperdicio debe ser menor que los kilogramos totales']
                    ]);
                }
            } else {
                // Para FormData
                $validated = $request->validate([
                    'nombre' => 'sometimes|required|string|max:100',
                    'categoria_id' => 'sometimes|required|exists:categorias,id',
                    'kilogramos' => 'sometimes|required|numeric|min:0.001|decimal:0,3',
                    'precio_compra' => 'sometimes|required|numeric|min:0|decimal:0,2',
                    'precio_venta_kg' => 'sometimes|required|numeric|min:0|decimal:0,2',
                    'proveedor_id' => 'sometimes|required|exists:proveedores,id',
                    'desperdicio' => 'nullable|numeric|min:0|decimal:0,3|lt:kilogramos',
                    'usuario_id' => 'sometimes|required|exists:usuarios,id',
                    'detalle' => 'nullable|string|max:500'
                ]);
            }
            
            $actualizado = $this->productoRepository->update($id, $validated);
            
            if (!$actualizado) {
                throw new \Exception('No se pudo actualizar el producto');
            }
            
            $producto = $this->productoRepository->findOrFail($id);
            
            DB::commit();
            
            // Respuesta para JSON
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto actualizado exitosamente',
                    'data' => new ProductoResource($producto)
                ], 200);
            }
            
            // Respuesta para web
            return redirect()->route('productos.index')
                ->with('success', 'Producto actualizado exitosamente.');
                
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el producto: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Error al actualizar el producto: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage (WEB y API).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $eliminado = $this->productoRepository->delete($id);
            
            if (!$eliminado) {
                throw new \Exception('No se pudo eliminar el producto');
            }
            
            // Respuesta para JSON
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto eliminado exitosamente'
                ], 200);
            }
            
            // Respuesta para web
            return redirect()->route('productos.index')
                ->with('success', 'Producto eliminado exitosamente');
                
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el producto: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Error al eliminar el producto: ' . $e->getMessage());
        }
    }

    /**
     * API específica para listar (mantenemos por compatibilidad)
     */
    public function apiIndex(Request $request)
    {
        return $this->index($request);
    }

    /**
     * API específica para mostrar (mantenemos por compatibilidad)
     */
    public function apiShow(Request $request, $id)
    {
        return $this->show($request, $id);
    }

    /**
     * API específica para crear (mantenemos por compatibilidad)
     */
    public function apiStore(Request $request)
    {
        return $this->store($request);
    }

    /**
     * API específica para actualizar (mantenemos por compatibilidad)
     */
    public function apiUpdate(Request $request, $id)
    {
        return $this->update($request, $id);
    }

    /**
     * API específica para eliminar (mantenemos por compatibilidad)
     */
    public function apiDestroy(Request $request, $id)
    {
        return $this->destroy($request, $id);
    }

    /**
     * Reporte de productos (WEB y API)
     */
    public function reporte(Request $request)
    {
        $filters = $request->only(['fecha_inicio', 'fecha_fin', 'categoria_id', 'proveedor_id']);
        $productos = $this->productoRepository->all();
        $estadisticas = $this->productoRepository->getEstadisticas($filters);
        
        // Si es una petición JSON/API
        if ($request->expectsJson() || $request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reporte generado exitosamente',
                'estadisticas' => $estadisticas,
                'data' => ProductoResource::collection($productos),
                'filtros_aplicados' => $filters
            ]);
        }
        
        // Si es una petición web (HTML)
        return view('productos.reporte', compact('productos', 'estadisticas'));
    }

    /**
     * Productos por categoría (WEB y API)
     */
    public function porCategoria(Request $request, $categoriaId)
    {
        try {
            $productos = $this->productoRepository->getPorCategoria($categoriaId);
            $categoria = Categoria::findOrFail($categoriaId);
            
            // Si es una petición JSON/API
            if ($request->expectsJson() || $request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Productos por categoría',
                    'categoria' => $categoria->nombre,
                    'data' => ProductoResource::collection($productos),
                    'total_productos' => $productos->count()
                ]);
            }
            
            // Si es una petición web (HTML)
            return view('productos.por-categoria', compact('productos', 'categoria'));
            
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada: ' . $e->getMessage()
                ], 404);
            }
            
            return redirect()->route('productos.index')
                ->with('error', 'Categoría no encontrada');
        }
    }

    /**
     * Productos con alto desperdicio (WEB y API)
     */
    public function altoDesperdicio(Request $request)
    {
        $productos = $this->productoRepository->getConAltoDesperdicio();
        
        // Si es una petición JSON/API
        if ($request->expectsJson() || $request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Productos con alto desperdicio',
                'data' => ProductoResource::collection($productos),
                'total_productos' => $productos->count(),
                'umbral_desperdicio' => '30%'
            ]);
        }
        
        // Si es una petición web (HTML)
        return view('productos.alto-desperdicio', compact('productos'));
    }
}