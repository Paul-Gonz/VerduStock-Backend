<?php
// app/Http\Controllers/Reportes/ReporteController.php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Repositories\ProductoRepository;
use App\Models\Categoria;
use App\Models\Proveedores;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReporteController extends Controller
{
    protected $productoRepository;

    public function __construct(ProductoRepository $productoRepository)
    {
        $this->productoRepository = $productoRepository;
    }

    /**
     * Generar reporte de inventario completo (PDF) - DISEÑO COMPACTO
     */
    public function inventarioCompleto(Request $request)
    {
        try {
            $filters = $request->only(['categoria_id', 'proveedor_id', 'estado_stock']);
            $filters['reporte'] = true;
            
            $productos = $this->productoRepository->search($filters);
            
            // Calcular estadísticas CORREGIDAS
            $totalKilosBrutos = $productos->sum('kilogramos');
            $totalDesperdicio = $productos->sum('desperdicio');
            $totalKilosNetos = $totalKilosBrutos - $totalDesperdicio;
            
            // Calcular ganancia total CORREGIDA
            $totalGanancia = $productos->sum(function($p) {
                $kilosNetos = $p->kilogramos - ($p->desperdicio ?? 0);
                $gananciaPorKg = $p->precio_venta_kg - $p->precio_compra;
                return $gananciaPorKg * $kilosNetos;
            });
            
            // Calcular ganancia perdida por desperdicio
            $gananciaPerdidaDesperdicio = $productos->sum(function($p) {
                $desperdicio = $p->desperdicio ?? 0;
                $gananciaPorKg = $p->precio_venta_kg - $p->precio_compra;
                return $gananciaPorKg * $desperdicio;
            });
            
            $estadisticas = [
                'total_productos' => $productos->count(),
                'total_kilos_brutos' => number_format($totalKilosBrutos, 2),
                'total_desperdicio' => number_format($totalDesperdicio, 2),
                'total_kilos_netos' => number_format($totalKilosNetos, 2),
                'porcentaje_desperdicio' => $totalKilosBrutos > 0 ? number_format(($totalDesperdicio / $totalKilosBrutos) * 100, 2) : '0.00',
                'valor_inventario_neto' => number_format($productos->sum(function($p) {
                    $kilosNetos = $p->kilogramos - ($p->desperdicio ?? 0);
                    return $kilosNetos * $p->precio_venta_kg;
                }), 2),
                'ganancia_perdida_desperdicio' => number_format($gananciaPerdidaDesperdicio, 2),
                'ganancia_total_proyectada' => number_format($totalGanancia, 2),
            ];
            
            // Generar HTML directamente
            $html = $this->generarHTMLInventarioCompleto($productos, $estadisticas, $filters);
            
            // Generar PDF con el HTML
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf->download('reporte-inventario-' . date('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar HTML para el reporte de inventario - DISEÑO COMPACTO
     */
    private function generarHTMLInventarioCompleto($productos, $estadisticas, $filters)
    {
        $filtrosTexto = $this->generarTextoFiltros($filters);
        // Obtener hora de Venezuela (UTC-4)
        $fechaVenezuela = Carbon::now('America/Caracas')->format('d/m/Y H:i:s');
        $usuario = auth()->user()->name ?? 'Sistema';
        $empresa = 'Sistema de Inventario'; // Puedes cambiar esto por el nombre real de tu empresa
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Reporte de Inventario Completo</title>
            <style>
                body { 
                    font-family: DejaVu Sans, Arial, sans-serif; 
                    font-size: 9px;
                    margin: 0;
                    padding: 15px;
                    color: #333;
                    line-height: 1.3;
                }
                
                /* ENCABEZADO SIMPLE */
                .header {
                    text-align: center;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #22c55e;
                }
                
                .company-name {
                    font-size: 18px;
                    font-weight: 800;
                    color: #22c55e;
                    margin: 0 0 5px 0;
                }
                
                .report-title {
                    font-size: 14px;
                    font-weight: 700;
                    color: #333;
                    margin: 0 0 3px 0;
                }
                
                .report-subtitle {
                    font-size: 11px;
                    color: #666;
                    margin: 0 0 10px 0;
                }
                
                .report-meta {
                    font-size: 8px;
                    color: #666;
                    text-align: center;
                    margin-bottom: 15px;
                }
                
                /* ESTADÍSTICAS COMPACTAS - UNA SOLA FILA */
                .stats-container {
                    margin: 15px 0;
                    text-align: center;
                }
                
                .stats-title {
                    font-size: 11px;
                    font-weight: 700;
                    color: #15803d;
                    margin-bottom: 10px;
                    text-align: left;
                    padding-left: 8px;
                    border-left: 3px solid #22c55e;
                }
                
                .stats-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                
                .stats-table td {
                    text-align: center;
                    vertical-align: top;
                    padding: 10px 4px;
                    border: 1px solid #e5e7eb;
                    background: #f8fafc;
                }
                
                .stat-number {
                    font-size: 20px;
                    font-weight: 800;
                    color: #15803d;
                    display: block;
                    margin-bottom: 3px;
                }
                
                .stat-label {
                    font-size: 8px;
                    color: #4b5563;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.2px;
                }
                
                .stat-danger .stat-number {
                    color: #dc2626;
                }
                
                .stat-warning .stat-number {
                    color: #d97706;
                }
                
                .stat-success .stat-number {
                    color: #16a34a;
                }
                
                /* FILTROS */
                .filters-box {
                    background: #f0fdf4;
                    border: 1px solid #bbf7d0;
                    border-radius: 6px;
                    padding: 8px 10px;
                    margin: 15px 0;
                    font-size: 8.5px;
                }
                
                .filters-title {
                    font-weight: 600;
                    color: #15803d;
                    margin-bottom: 3px;
                    font-size: 9px;
                }
                
                /* TABLA DE PRODUCTOS */
                .table-container {
                    margin: 20px 0;
                }
                
                .table-title {
                    font-size: 12px;
                    font-weight: 700;
                    color: #15803d;
                    margin-bottom: 10px;
                    text-align: left;
                    padding-left: 8px;
                    border-left: 3px solid #22c55e;
                }
                
                table { 
                    width: 100%; 
                    border-collapse: collapse;
                    margin-top: 8px; 
                    font-size: 8.5px;
                    border: 1px solid #e5e7eb;
                }
                
                th { 
                    background: #22c55e; 
                    color: white; 
                    font-weight: 700; 
                    padding: 6px 5px; 
                    text-align: left; 
                    font-size: 8.5px;
                    text-transform: uppercase;
                    border: 1px solid #16a34a;
                }
                
                td { 
                    padding: 5px; 
                    border: 1px solid #e5e7eb;
                    font-size: 8.5px;
                }
                
                tr:nth-child(even) { 
                    background: #f9fafb; 
                }
                
                .text-right { 
                    text-align: right; 
                }
                
                .text-center { 
                    text-align: center; 
                }
                
                /* BADGES */
                .badge { 
                    display: inline-block; 
                    padding: 2px 6px; 
                    border-radius: 10px; 
                    font-size: 7.5px; 
                    font-weight: 700; 
                    text-transform: uppercase;
                }
                
                .badge-success { 
                    background: #dcfce7; 
                    color: #166534; 
                }
                
                .badge-warning { 
                    background: #fef3c7; 
                    color: #92400e; 
                }
                
                .badge-danger { 
                    background: #fee2e2; 
                    color: #991b1b; 
                }
                
                /* COLORES DE FILAS */
                .desperdicio-alto {
                    background: #fef2f2 !important;
                }
                
                .desperdicio-moderado {
                    background: #fffbeb !important;
                }
                
                /* TOTALS FOOTER */
                tfoot tr {
                    background: #f0fdf4 !important;
                    font-weight: 700;
                }
                
                tfoot td {
                    border-top: 2px solid #22c55e;
                }
                
                /* FOOTER */
                .footer { 
                    margin-top: 20px; 
                    padding-top: 10px; 
                    border-top: 1px solid #e5e7eb; 
                    font-size: 7.5px; 
                    color: #666; 
                    text-align: center;
                }
                
                .no-data {
                    text-align: center;
                    padding: 20px 15px;
                    color: #666;
                    font-style: italic;
                    background: #f9fafb;
                    border-radius: 6px;
                    border: 1px dashed #d1d5db;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1 class="company-name">' . $empresa . '</h1>
                <h2 class="report-title">Reporte de Inventario Completo</h2>
                <div class="report-meta">
                    Fecha: ' . $fechaVenezuela . ' (Venezuela) | Usuario: ' . $usuario . '
                </div>
            </div>';
            
        if ($filtrosTexto) {
            $html .= '<div class="filters-box">
                <div class="filters-title">Filtros aplicados:</div>
                ' . $filtrosTexto . '
            </div>';
        }
        
        // ESTADÍSTICAS EN UNA SOLA FILA COMO EN LA IMAGEN
        $html .= '
            <div class="stats-container">
                <div class="stats-title">ESTADÍSTICAS GENERALES</div>
                <table class="stats-table">
                    <tr>
                        <td>
                            <span class="stat-number">' . $estadisticas['total_productos'] . '</span>
                            <span class="stat-label">Total Productos</span>
                        </td>
                        <td>
                            <span class="stat-number">' . $estadisticas['total_kilos_netos'] . ' kg</span>
                            <span class="stat-label">Stock Neto</span>
                        </td>
                        <td class="stat-success">
                            <span class="stat-number">$' . $estadisticas['ganancia_total_proyectada'] . '</span>
                            <span class="stat-label">Venta Neta</span>
                        </td>
                        <td>
                            <span class="stat-number">$' . $estadisticas['valor_inventario_neto'] . '</span>
                            <span class="stat-label">Venta Bruta</span>
                        </td>
                    </tr>
                </table>
                
                <table class="stats-table">
                    <tr>
                        <td class="stat-danger">
                            <span class="stat-number">' . $estadisticas['total_desperdicio'] . ' kg</span>
                            <span class="stat-label">Desperdicio Total</span>
                        </td>
                        <td class="stat-danger">
                            <span class="stat-number">' . $estadisticas['porcentaje_desperdicio'] . '%</span>
                            <span class="stat-label">% Desperdicio</span>
                        </td>
                        <td class="stat-danger">
                            <span class="stat-number">$' . $estadisticas['ganancia_perdida_desperdicio'] . '</span>
                            <span class="stat-label">Perdida en Desperdicio</span>
                        </td>
                        <td class="stat-warning">
                            <span class="stat-number">' . $estadisticas['total_kilos_brutos'] . ' kg</span>
                            <span class="stat-label">Stock Bruto</span>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="table-container">
                <div class="table-title">DETALLE DE PRODUCTOS</div>';
            
        if ($productos->count() > 0) {
            $html .= '
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th class="text-right">Stock Bruto</th>
                        <th class="text-right">Desperdicio</th>
                        <th class="text-right">Stock Neto</th>
                        <th class="text-right">P. Compra(Kg)</th>
                        <th class="text-right">P. Venta(Kg)</th>
                        <th class="text-right">Ganancia(kg)</th>
                        <th class="text-right">Ganancia Total</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>';
            
            $totalKilosNetos = 0;
            $totalValorNeto = 0;
            $totalDesperdicio = 0;
            $totalGanancia = 0;
            
            foreach ($productos as $index => $producto) {
                $desperdicio = $producto->desperdicio ?? 0;
                $kilosNetos = $producto->kilogramos - $desperdicio;
                $valorNeto = $kilosNetos * $producto->precio_venta_kg;
                $gananciaPorKg = $producto->precio_venta_kg - $producto->precio_compra;
                $gananciaTotal = $gananciaPorKg * $kilosNetos;
                
                $totalKilosNetos += $kilosNetos;
                $totalValorNeto += $valorNeto;
                $totalDesperdicio += $desperdicio;
                $totalGanancia += $gananciaTotal;
                
                // Determinar clase por nivel de desperdicio
                $claseFila = '';
                if ($desperdicio > 0) {
                    $porcentajeDesperdicio = $producto->kilogramos > 0 ? ($desperdicio / $producto->kilogramos) * 100 : 0;
                    if ($porcentajeDesperdicio > 20) {
                        $claseFila = 'desperdicio-alto';
                    } elseif ($porcentajeDesperdicio > 5) {
                        $claseFila = 'desperdicio-moderado';
                    }
                }
                
                // Determinar estado de stock
                $estadoClase = 'badge-success';
                $estadoTexto = 'Normal';
                
                if ($kilosNetos <= 10) {
                    $estadoClase = 'badge-danger';
                    $estadoTexto = 'Bajo';
                } elseif ($kilosNetos <= 20) {
                    $estadoClase = 'badge-warning';
                    $estadoTexto = 'Medio';
                }
                
                $html .= '
                    <tr class="' . $claseFila . '">
                        <td>' . ($index + 1) . '</td>
                        <td>' . htmlspecialchars($producto->nombre) . '</td>
                        <td>' . htmlspecialchars($producto->categoria->nombre ?? 'Sin categoría') . '</td>
                        <td class="text-right">' . number_format($producto->kilogramos, 2) . '</td>
                        <td class="text-right">' . ($desperdicio > 0 ? number_format($desperdicio, 2) : '0.00') . '</td>
                        <td class="text-right">' . number_format($kilosNetos, 2) . '</td>
                        <td class="text-right">$' . number_format($producto->precio_compra, 2) . '</td>
                        <td class="text-right">$' . number_format($producto->precio_venta_kg, 2) . '</td>
                        <td class="text-right">$' . number_format($gananciaPorKg, 2) . '</td>
                        <td class="text-right">$' . number_format($gananciaTotal, 2) . '</td>
                        <td class="text-center">
                            <span class="badge ' . $estadoClase . '">' . $estadoTexto . '</span>
                        </td>
                    </tr>';
            }
            
            $html .= '
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>TOTALES:</strong></td>
                        <td class="text-right"><strong>' . number_format($productos->sum('kilogramos'), 2) . ' kg</strong></td>
                        <td class="text-right"><strong>' . number_format($totalDesperdicio, 2) . ' kg</strong></td>
                        <td class="text-right"><strong>' . number_format($totalKilosNetos, 2) . ' kg</strong></td>
                        <td colspan="2"></td>
                        <td></td>
                        <td class="text-right"><strong>$' . number_format($totalGanancia, 2) . '</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>';
        } else {
            $html .= '<div class="no-data">No hay productos que coincidan con los criterios seleccionados.</div>';
        }
        
        $html .= '</div>
            <div class="footer">
                Documento generado automáticamente por ' . $empresa . ' | Fecha: ' . $fechaVenezuela . ' (Venezuela)
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Reporte de stock bajo (PDF) - DISEÑO COMPACTO
     */
    public function stockBajo(Request $request)
    {
        try {
            $umbral = $request->get('umbral', 10);
            
            $productos = $this->productoRepository->search(['reporte' => true])
                ->filter(function($producto) use ($umbral) {
                    $kilosNetos = $producto->kilogramos - ($producto->desperdicio ?? 0);
                    return $kilosNetos <= $umbral;
                });
            
            $estadisticas = [
                'total_productos' => $productos->count(),
                'productos_criticos' => $productos->filter(function($p) use ($umbral) {
                    $kilosNetos = $p->kilogramos - ($p->desperdicio ?? 0);
                    return $kilosNetos <= ($umbral * 0.5);
                })->count(),
                'total_valor_riesgo' => number_format($productos->sum(function($p) {
                    $kilosNetos = $p->kilogramos - ($p->desperdicio ?? 0);
                    return $kilosNetos * $p->precio_compra;
                }), 2),
                'total_desperdicio' => number_format($productos->sum('desperdicio'), 2),
                'ganancia_perdida' => number_format($productos->sum(function($p) {
                    $kilosNetos = $p->kilogramos - ($p->desperdicio ?? 0);
                    $gananciaPorKg = $p->precio_venta_kg - $p->precio_compra;
                    return $gananciaPorKg * $kilosNetos;
                }), 2),
            ];
            
            // Generar HTML directamente
            $html = $this->generarHTMLStockBajo($productos, $estadisticas, $umbral);
            
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf->download('reporte-stock-bajo-' . date('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar HTML para reporte de stock bajo - DISEÑO COMPACTO
     */
    private function generarHTMLStockBajo($productos, $estadisticas, $umbral)
    {
        // Obtener hora de Venezuela (UTC-4)
        $fechaVenezuela = Carbon::now('America/Caracas')->format('d/m/Y H:i:s');
        $usuario = auth()->user()->name ?? 'Sistema';
        $empresa = 'Sistema de Inventario';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Reporte de Stock Bajo</title>
            <style>
                body { 
                    font-family: DejaVu Sans, Arial, sans-serif; 
                    font-size: 9px;
                    margin: 0;
                    padding: 15px;
                    color: #333;
                    line-height: 1.3;
                }
                
                /* ENCABEZADO */
                .header {
                    text-align: center;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #dc2626;
                }
                
                .company-name {
                    font-size: 18px;
                    font-weight: 800;
                    color: #dc2626;
                    margin: 0 0 5px 0;
                }
                
                .report-title {
                    font-size: 14px;
                    font-weight: 700;
                    color: #333;
                    margin: 0 0 3px 0;
                }
                
                .report-subtitle {
                    font-size: 11px;
                    color: #dc2626;
                    margin: 0 0 8px 0;
                    font-weight: 600;
                }
                
                .report-meta {
                    font-size: 8px;
                    color: #666;
                    text-align: center;
                    margin-bottom: 15px;
                }
                
                /* ESTADÍSTICAS COMPACTAS */
                .stats-container {
                    margin: 15px 0;
                    text-align: center;
                }
                
                .stats-title {
                    font-size: 11px;
                    font-weight: 700;
                    color: #dc2626;
                    margin-bottom: 10px;
                    text-align: left;
                    padding-left: 8px;
                    border-left: 3px solid #dc2626;
                }
                
                .stats-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                
                .stats-table td {
                    text-align: center;
                    vertical-align: top;
                    padding: 10px 4px;
                    border: 1px solid #e5e7eb;
                    background: #f8fafc;
                }
                
                .stat-number {
                    font-size: 20px;
                    font-weight: 800;
                    color: #dc2626;
                    display: block;
                    margin-bottom: 3px;
                }
                
                .stat-label {
                    font-size: 8px;
                    color: #4b5563;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.2px;
                }
                
                .stat-warning .stat-number {
                    color: #d97706;
                }
                
                /* TABLA */
                .table-container {
                    margin: 20px 0;
                }
                
                .table-title {
                    font-size: 12px;
                    font-weight: 700;
                    color: #dc2626;
                    margin-bottom: 10px;
                    text-align: left;
                    padding-left: 8px;
                    border-left: 3px solid #dc2626;
                }
                
                table { 
                    width: 100%; 
                    border-collapse: collapse;
                    margin-top: 8px; 
                    font-size: 8.5px;
                    border: 1px solid #e5e7eb;
                }
                
                th { 
                    background: #dc2626; 
                    color: white; 
                    font-weight: 700; 
                    padding: 6px 5px; 
                    text-align: left; 
                    font-size: 8.5px;
                    text-transform: uppercase;
                    border: 1px solid #991b1b;
                }
                
                td { 
                    padding: 5px; 
                    border: 1px solid #e5e7eb;
                    font-size: 8.5px;
                }
                
                tr:nth-child(even) { 
                    background: #f9fafb; 
                }
                
                .text-right { 
                    text-align: right; 
                }
                
                .text-center { 
                    text-align: center; 
                }
                
                .urgente { 
                    background: #fef2f2 !important; 
                }
                
                .advertencia { 
                    background: #fffbeb !important; 
                }
                
                /* FOOTER */
                .footer { 
                    margin-top: 20px; 
                    padding-top: 10px; 
                    border-top: 1px solid #e5e7eb; 
                    font-size: 7.5px; 
                    color: #666; 
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1 class="company-name">' . $empresa . '</h1>
                <h2 class="report-title">REPORTE DE STOCK BAJO</h2>
                <p class="report-subtitle">Stock neto por debajo de ' . $umbral . ' kg</p>
                <div class="report-meta">
                    Fecha: ' . $fechaVenezuela . ' (Venezuela) | Usuario: ' . $usuario . '
                </div>
            </div>
            
            <div class="stats-container">
                <div class="stats-title">ESTADÍSTICAS DE STOCK BAJO</div>
                <table class="stats-table">
                    <tr>
                        <td>
                            <span class="stat-number">' . $estadisticas['total_productos'] . '</span>
                            <span class="stat-label">Productos con stock bajo</span>
                        </td>
                        <td class="stat-warning">
                            <span class="stat-number">' . $estadisticas['productos_criticos'] . '</span>
                            <span class="stat-label">Críticos (&lt;= ' . ($umbral * 0.5) . ' kg)</span>
                        </td>
                        <!--<td>
                            <span class="stat-number">$' . $estadisticas['total_valor_riesgo'] . '</span>
                            <span class="stat-label">Valor en riesgo</span>
                        </td>
                        <td>
                            <span class="stat-number">' . $estadisticas['total_desperdicio'] . ' kg</span>
                            <span class="stat-label">Desperdicio total</span>
                        </td>
                        <td>
                            <span class="stat-number">$' . $estadisticas['ganancia_perdida'] . '</span>
                            <span class="stat-label">Ganancia en riesgo</span>
                        </td>-->
                    </tr>
                </table>
            </div>';
            
        if ($productos->count() > 0) {
            $html .= '
            <div class="table-container">
                <div class="table-title">DETALLE DE PRODUCTOS CON STOCK BAJO</div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-right">Stock Neto (kg)</th>
                            <th class="text-right">Desperdicio (kg)</th>
                            <th class="text-right">Ganancia/kg</th>
                            <th class="text-right">Ganancia Total</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($productos as $index => $producto) {
                $kilosNetos = $producto->kilogramos - ($producto->desperdicio ?? 0);
                $gananciaPorKg = $producto->precio_venta_kg - $producto->precio_compra;
                $gananciaTotal = $gananciaPorKg * $kilosNetos;
                
                $claseFila = 'advertencia';
                $estado = '<span style="color: #d97706; font-weight: 700;">ADVERTENCIA</span>';
                
                if ($kilosNetos <= ($umbral * 0.5)) {
                    $claseFila = 'urgente';
                    $estado = '<span style="color: #dc2626; font-weight: 700;">CRÍTICO</span>';
                }
                
                $html .= '
                    <tr class="' . $claseFila . '">
                        <td>' . ($index + 1) . '</td>
                        <td>' . htmlspecialchars($producto->nombre) . '</td>
                        <td>' . htmlspecialchars($producto->categoria->nombre ?? 'Sin categoría') . '</td>
                        <td class="text-right">' . number_format($kilosNetos, 2) . '</td>
                        <td class="text-right">' . number_format($producto->desperdicio ?? 0, 2) . '</td>
                        <td class="text-right">$' . number_format($gananciaPorKg, 2) . '</td>
                        <td class="text-right">$' . number_format($gananciaTotal, 2) . '</td>
                        <td class="text-center">' . $estado . '</td>
                    </tr>';
            }
            
            $html .= '</tbody></table></div>';
        } else {
            $html .= '<div style="text-align: center; padding: 20px 15px; background: #f0fdf4; border-radius: 6px; border: 1px solid #bbf7d0;">
                <p style="color: #666; margin: 0; font-size: 9px;">¡Excelente! No hay productos con stock bajo.</p>
            </div>';
        }
        
        $html .= '
            <div class="footer">
                Documento generado automáticamente por ' . $empresa . ' | Fecha: ' . $fechaVenezuela . ' (Venezuela)
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Reporte de desperdicios acumulados (PDF) - DISEÑO COMPACTO
     */
    public function reporteDesperdicios(Request $request)
    {
        try {
            $filters = $request->only(['categoria_id', 'proveedor_id']);
            $filters['reporte'] = true;
            
            $productos = $this->productoRepository->search($filters);
            
            // Filtrar productos con desperdicio
            $productosConDesperdicio = $productos->filter(function($producto) {
                return ($producto->desperdicio ?? 0) > 0;
            });
            
            $estadisticas = [
                'total_productos' => $productosConDesperdicio->count(),
                'total_desperdicio_kg' => number_format($productosConDesperdicio->sum('desperdicio'), 2),
                'ganancia_perdida' => number_format($productosConDesperdicio->sum(function($p) {
                    $desperdicio = $p->desperdicio ?? 0;
                    $gananciaPorKg = $p->precio_venta_kg - $p->precio_compra;
                    return $gananciaPorKg * $desperdicio;
                }), 2),
                'productos_alto_desperdicio' => $productosConDesperdicio->filter(function($p) {
                    $porcentaje = $p->kilogramos > 0 ? (($p->desperdicio ?? 0) / $p->kilogramos) * 100 : 0;
                    return $porcentaje > 20;
                })->count(),
                'productos_moderado_desperdicio' => $productosConDesperdicio->filter(function($p) {
                    $porcentaje = $p->kilogramos > 0 ? (($p->desperdicio ?? 0) / $p->kilogramos) * 100 : 0;
                    return $porcentaje > 5 && $porcentaje <= 20;
                })->count(),
            ];
            
            // Generar HTML directamente
            $html = $this->generarHTMLDesperdicios($productosConDesperdicio, $estadisticas, $filters);
            
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf->download('reporte-desperdicios-' . date('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar HTML para reporte de desperdicios - DISEÑO COMPACTO
     */
    private function generarHTMLDesperdicios($productos, $estadisticas, $filters)
    {
        $filtrosTexto = $this->generarTextoFiltros($filters);
        // Obtener hora de Venezuela (UTC-4)
        $fechaVenezuela = Carbon::now('America/Caracas')->format('d/m/Y H:i:s');
        $usuario = auth()->user()->name ?? 'Sistema';
        $empresa = 'Sistema de Inventario';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Reporte de Desperdicios</title>
            <style>
                body { 
                    font-family: DejaVu Sans, Arial, sans-serif; 
                    font-size: 9px;
                    margin: 0;
                    padding: 15px;
                    color: #333;
                    line-height: 1.3;
                }
                
                /* ENCABEZADO */
                .header {
                    text-align: center;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #ea580c;
                }
                
                .company-name {
                    font-size: 18px;
                    font-weight: 800;
                    color: #ea580c;
                    margin: 0 0 5px 0;
                }
                
                .report-title {
                    font-size: 14px;
                    font-weight: 700;
                    color: #333;
                    margin: 0 0 3px 0;
                }
                
                .report-meta {
                    font-size: 8px;
                    color: #666;
                    text-align: center;
                    margin-bottom: 15px;
                }
                
                /* ESTADÍSTICAS COMPACTAS */
                .stats-container {
                    margin: 15px 0;
                    text-align: center;
                }
                
                .stats-title {
                    font-size: 11px;
                    font-weight: 700;
                    color: #ea580c;
                    margin-bottom: 10px;
                    text-align: left;
                    padding-left: 8px;
                    border-left: 3px solid #ea580c;
                }
                
                .stats-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                
                .stats-table td {
                    text-align: center;
                    vertical-align: top;
                    padding: 10px 4px;
                    border: 1px solid #e5e7eb;
                    background: #f8fafc;
                }
                
                .stat-number {
                    font-size: 20px;
                    font-weight: 800;
                    color: #ea580c;
                    display: block;
                    margin-bottom: 3px;
                }
                
                .stat-label {
                    font-size: 8px;
                    color: #4b5563;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.2px;
                }
                
                .stat-danger .stat-number {
                    color: #dc2626;
                }
                
                .stat-warning .stat-number {
                    color: #d97706;
                }
                
                /* FILTROS */
                .filters-box {
                    background: #fff7ed;
                    border: 1px solid #fdba74;
                    border-radius: 6px;
                    padding: 8px 10px;
                    margin: 15px 0;
                    font-size: 8.5px;
                }
                
                .filters-title {
                    font-weight: 600;
                    color: #ea580c;
                    margin-bottom: 3px;
                    font-size: 9px;
                }
                
                /* TABLA */
                .table-container {
                    margin: 20px 0;
                }
                
                .table-title {
                    font-size: 12px;
                    font-weight: 700;
                    color: #ea580c;
                    margin-bottom: 10px;
                    text-align: left;
                    padding-left: 8px;
                    border-left: 3px solid #ea580c;
                }
                
                table { 
                    width: 100%; 
                    border-collapse: collapse;
                    margin-top: 8px; 
                    font-size: 8.5px;
                    border: 1px solid #e5e7eb;
                }
                
                th { 
                    background: #ea580c; 
                    color: white; 
                    font-weight: 700; 
                    padding: 6px 5px; 
                    text-align: left; 
                    font-size: 8.5px;
                    text-transform: uppercase;
                    border: 1px solid #c2410c;
                }
                
                td { 
                    padding: 5px; 
                    border: 1px solid #e5e7eb;
                    font-size: 8.5px;
                }
                
                tr:nth-child(even) { 
                    background: #f9fafb; 
                }
                
                .text-right { 
                    text-align: right; 
                }
                
                .text-center { 
                    text-align: center; 
                }
                
                .alto { 
                    background: #fef2f2 !important; 
                }
                
                .moderado { 
                    background: #fffbeb !important; 
                }
                
                .bajo { 
                    background: #f0fdf4 !important; 
                }
                
                /* FOOTER */
                .footer { 
                    margin-top: 20px; 
                    padding-top: 10px; 
                    border-top: 1px solid #e5e7eb; 
                    font-size: 7.5px; 
                    color: #666; 
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1 class="company-name">' . $empresa . '</h1>
                <h2 class="report-title">REPORTE DE DESPERDICIOS</h2>
                <div class="report-meta">
                    Fecha: ' . $fechaVenezuela . ' (Venezuela) | Usuario: ' . $usuario . '
                </div>
            </div>';
                
        if ($filtrosTexto) {
            $html .= '<div class="filters-box">
                <div class="filters-title">Filtros aplicados:</div>
                ' . $filtrosTexto . '
            </div>';
        }
        
        $html .= '
            <div class="stats-container">
                <div class="stats-title">ESTADÍSTICAS DE DESPERDICIOS</div>
                <table class="stats-table">
                    <tr>
                        <td>
                            <span class="stat-number">' . $estadisticas['total_productos'] . '</span>
                            <span class="stat-label">Productos con desperdicio</span>
                        </td>
                        <td class="stat-danger">
                            <span class="stat-number">' . $estadisticas['total_desperdicio_kg'] . ' kg</span>
                            <span class="stat-label">Desperdicio Total</span>
                        </td>
                        <td class="stat-danger">
                            <span class="stat-number">$' . $estadisticas['ganancia_perdida'] . '</span>
                            <span class="stat-label">Perdida en desperdicio</span>
                        </td>
                        <td>
                            <span class="stat-number">' . $estadisticas['productos_alto_desperdicio'] . '</span>
                            <span class="stat-label">Alto desperdicio (>20%)</span>
                        </td>
                        <td>
                            <span class="stat-number">' . $estadisticas['productos_moderado_desperdicio'] . '</span>
                            <span class="stat-label">Moderado (5-20%)</span>
                        </td>
                    </tr>
                </table>
            </div>';
            
        if ($productos->count() > 0) {
            // Ordenar por porcentaje de desperdicio (mayor a menor)
            $productosOrdenados = $productos->sortByDesc(function($producto) {
                return $producto->kilogramos > 0 ? (($producto->desperdicio ?? 0) / $producto->kilogramos) * 100 : 0;
            });
            
            $html .= '
            <div class="table-container">
                <div class="table-title">DETALLE DE PRODUCTOS CON DESPERDICIO</div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-right">Stock Bruto (kg)</th>
                            <th class="text-right">Desperdicio (kg)</th>
                            <th class="text-right">% Desperdicio</th>
                            <th class="text-right">P. Venta/kg</th>
                            <th class="text-right">Ganancia/kg</th>
                            <th class="text-right">Perdida en desperdicio</th>
                            <th class="text-center">Nivel</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($productosOrdenados as $index => $producto) {
                $desperdicio = $producto->desperdicio ?? 0;
                $porcentaje = $producto->kilogramos > 0 ? ($desperdicio / $producto->kilogramos) * 100 : 0;
                $gananciaPorKg = $producto->precio_venta_kg - $producto->precio_compra;
                $gananciaPerdida = $desperdicio * $gananciaPorKg;
                
                // Determinar nivel
                $claseFila = 'bajo';
                $nivel = '<span style="color: #166534; font-weight: 700;">BAJO</span>';
                
                if ($porcentaje > 20) {
                    $claseFila = 'alto';
                    $nivel = '<span style="color: #dc2626; font-weight: 700;">ALTO</span>';
                } elseif ($porcentaje > 5) {
                    $claseFila = 'moderado';
                    $nivel = '<span style="color: #d97706; font-weight: 700;">MODERADO</span>';
                }
                
                $html .= '
                    <tr class="' . $claseFila . '">
                        <td>' . ($index + 1) . '</td>
                        <td>' . htmlspecialchars($producto->nombre) . '</td>
                        <td>' . htmlspecialchars($producto->categoria->nombre ?? 'Sin categoría') . '</td>
                        <td class="text-right">' . number_format($producto->kilogramos, 2) . '</td>
                        <td class="text-right">' . number_format($desperdicio, 2) . '</td>
                        <td class="text-right">' . number_format($porcentaje, 1) . '%</td>
                        <td class="text-right">$' . number_format($producto->precio_venta_kg, 2) . '</td>
                        <td class="text-right">$' . number_format($gananciaPorKg, 2) . '</td>
                        <td class="text-right">$' . number_format($gananciaPerdida, 2) . '</td>
                        <td class="text-center">' . $nivel . '</td>
                    </tr>';
            }
            
            $html .= '</tbody></table></div>';
        } else {
            $html .= '<div style="text-align: center; padding: 20px 15px; background: #f0fdf4; border-radius: 6px; border: 1px solid #bbf7d0;">
                <p style="color: #666; margin: 0; font-size: 9px;">¡Excelente! No hay productos con desperdicio registrado.</p>
            </div>';
        }
        
        $html .= '
            <div class="footer">
                Documento generado automáticamente por ' . $empresa . ' | Fecha: ' . $fechaVenezuela . ' (Venezuela)
            </div>
        </body>
        </html>';
        
        return $html;
    }

        /**
     * Reporte de análisis de rentabilidad (PDF) - DISEÑO COMPACTO
     */
    public function analisisRentabilidad(Request $request)
    {
        try {
            $filters = $request->only(['categoria_id', 'proveedor_id']);
            $filters['reporte'] = true;
            
            $productos = $this->productoRepository->search($filters);
            
            // Calcular márgenes de rentabilidad
            $productosConMargen = $productos->map(function($producto) {
                $desperdicio = $producto->desperdicio ?? 0;
                $kilosNetos = $producto->kilogramos - $desperdicio;
                $margenAbsoluto = $producto->precio_venta_kg - $producto->precio_compra;
                $margenPorcentual = $producto->precio_compra > 0 ? 
                    ($margenAbsoluto / $producto->precio_compra) * 100 : 0;
                $gananciaTotal = $margenAbsoluto * $kilosNetos;
                $gananciaPerdidaDesperdicio = $margenAbsoluto * $desperdicio;
                
                return (object) [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'categoria' => $producto->categoria,
                    'kilogramos' => $producto->kilogramos,
                    'desperdicio' => $desperdicio,
                    'kilos_netos' => $kilosNetos,
                    'precio_compra' => $producto->precio_compra,
                    'precio_venta_kg' => $producto->precio_venta_kg,
                    'margen_absoluto' => $margenAbsoluto,
                    'margen_porcentual' => $margenPorcentual,
                    'ganancia_total' => $gananciaTotal,
                    'ganancia_perdida_desperdicio' => $gananciaPerdidaDesperdicio,
                    'rentabilidad' => $this->clasificarRentabilidad($margenPorcentual),
                ];
            });
            
            // Ordenar por margen porcentual (mayor a menor)
            $productosOrdenados = $productosConMargen->sortByDesc('margen_porcentual');
            
            // Estadísticas de rentabilidad
            $estadisticas = [
                'total_productos' => $productosOrdenados->count(),
                'productos_alta_rentabilidad' => $productosOrdenados->where('rentabilidad', 'alta')->count(),
                'productos_media_rentabilidad' => $productosOrdenados->where('rentabilidad', 'media')->count(),
                'productos_baja_rentabilidad' => $productosOrdenados->where('rentabilidad', 'baja')->count(),
                'margen_promedio' => number_format($productosOrdenados->avg('margen_porcentual'), 2),
                'ganancia_total_proyectada' => number_format($productosOrdenados->sum('ganancia_total'), 2),
                'ganancia_perdida_desperdicio' => number_format($productosOrdenados->sum('ganancia_perdida_desperdicio'), 2),
                'producto_mas_rentable' => $productosOrdenados->first() ? $productosOrdenados->first()->nombre : 'N/A',
                'margen_mas_rentable' => $productosOrdenados->first() ? number_format($productosOrdenados->first()->margen_porcentual, 2) . '%' : 'N/A',
                'producto_menos_rentable' => $productosOrdenados->last() ? $productosOrdenados->last()->nombre : 'N/A',
                'margen_menos_rentable' => $productosOrdenados->last() ? number_format($productosOrdenados->last()->margen_porcentual, 2) . '%' : 'N/A',
            ];
            
            // Generar HTML directamente
            $html = $this->generarHTMLAnalisisRentabilidad($productosOrdenados, $estadisticas, $filters);
            
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf->download('analisis-rentabilidad-' . date('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar HTML para análisis de rentabilidad - DISEÑO COMPACTO
     */
    private function generarHTMLAnalisisRentabilidad($productos, $estadisticas, $filters)
    {
        $filtrosTexto = $this->generarTextoFiltros($filters);
        // Obtener hora de Venezuela (UTC-4)
        $fechaVenezuela = Carbon::now('America/Caracas')->format('d/m/Y H:i:s');
        $usuario = auth()->user()->name ?? 'Sistema';
        $empresa = 'Sistema de Inventario';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Análisis de Rentabilidad</title>
            <style>
                body { 
                    font-family: DejaVu Sans, Arial, sans-serif; 
                    font-size: 9px;
                    margin: 0;
                    padding: 15px;
                    color: #333;
                    line-height: 1.3;
                }
                
                /* ENCABEZADO */
                .header {
                    text-align: center;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #3b82f6;
                }
                
                .company-name {
                    font-size: 18px;
                    font-weight: 800;
                    color: #3b82f6;
                    margin: 0 0 5px 0;
                }
                
                .report-title {
                    font-size: 14px;
                    font-weight: 700;
                    color: #333;
                    margin: 0 0 3px 0;
                }
                
                .report-meta {
                    font-size: 8px;
                    color: #666;
                    text-align: center;
                    margin-bottom: 15px;
                }
                
                /* ESTADÍSTICAS COMPACTAS */
                .stats-container {
                    margin: 15px 0;
                    text-align: center;
                }
                
                .stats-title {
                    font-size: 11px;
                    font-weight: 700;
                    color: #3b82f6;
                    margin-bottom: 10px;
                    text-align: left;
                    padding-left: 8px;
                    border-left: 3px solid #3b82f6;
                }
                
                .stats-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                
                .stats-table td {
                    text-align: center;
                    vertical-align: top;
                    padding: 10px 4px;
                    border: 1px solid #e5e7eb;
                    background: #f8fafc;
                }
                
                .stat-number {
                    font-size: 20px;
                    font-weight: 800;
                    color: #3b82f6;
                    display: block;
                    margin-bottom: 3px;
                }
                
                .stat-label {
                    font-size: 8px;
                    color: #4b5563;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.2px;
                }
                
                .stat-success .stat-number {
                    color: #16a34a;
                }
                
                .stat-warning .stat-number {
                    color: #d97706;
                }
                
                .stat-danger .stat-number {
                    color: #dc2626;
                }
                
                /* FILTROS */
                .filters-box {
                    background: #eff6ff;
                    border: 1px solid #93c5fd;
                    border-radius: 6px;
                    padding: 8px 10px;
                    margin: 15px 0;
                    font-size: 8.5px;
                }
                
                .filters-title {
                    font-weight: 600;
                    color: #3b82f6;
                    margin-bottom: 3px;
                    font-size: 9px;
                }
                
                /* TABLA */
                .table-container {
                    margin: 20px 0;
                }
                
                .table-title {
                    font-size: 12px;
                    font-weight: 700;
                    color: #3b82f6;
                    margin-bottom: 10px;
                    text-align: left;
                    padding-left: 8px;
                    border-left: 3px solid #3b82f6;
                }
                
                table { 
                    width: 100%; 
                    border-collapse: collapse;
                    margin-top: 8px; 
                    font-size: 8.5px;
                    border: 1px solid #e5e7eb;
                }
                
                th { 
                    background: #3b82f6; 
                    color: white; 
                    font-weight: 700; 
                    padding: 6px 5px; 
                    text-align: left; 
                    font-size: 8.5px;
                    text-transform: uppercase;
                    border: 1px solid #1d4ed8;
                }
                
                td { 
                    padding: 5px; 
                    border: 1px solid #e5e7eb;
                    font-size: 8.5px;
                }
                
                tr:nth-child(even) { 
                    background: #f9fafb; 
                }
                
                .text-right { 
                    text-align: right; 
                }
                
                .text-center { 
                    text-align: center; 
                }
                
                .alta-rentabilidad { 
                    background: #f0fdf4 !important; 
                }
                
                .media-rentabilidad { 
                    background: #fffbeb !important; 
                }
                
                .baja-rentabilidad { 
                    background: #fef2f2 !important; 
                }
                
                /* FOOTER */
                .footer { 
                    margin-top: 20px; 
                    padding-top: 10px; 
                    border-top: 1px solid #e5e7eb; 
                    font-size: 7.5px; 
                    color: #666; 
                    text-align: center;
                }
                
                /* BADGES */
                .badge { 
                    display: inline-block; 
                    padding: 2px 6px; 
                    border-radius: 10px; 
                    font-size: 7.5px; 
                    font-weight: 700; 
                    text-transform: uppercase;
                }
                
                .badge-success { 
                    background: #dcfce7; 
                    color: #166534; 
                }
                
                .badge-warning { 
                    background: #fef3c7; 
                    color: #92400e; 
                }
                
                .badge-danger { 
                    background: #fee2e2; 
                    color: #991b1b; 
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1 class="company-name">' . $empresa . '</h1>
                <h2 class="report-title">ANÁLISIS DE RENTABILIDAD</h2>
                <div class="report-meta">
                    Fecha: ' . $fechaVenezuela . ' (Venezuela) | Usuario: ' . $usuario . '
                </div>
            </div>';
                
        if ($filtrosTexto) {
            $html .= '<div class="filters-box">
                <div class="filters-title">Filtros aplicados:</div>
                ' . $filtrosTexto . '
            </div>';
        }
        
        $html .= '
            <div class="stats-container">
                <div class="stats-title">ESTADÍSTICAS DE RENTABILIDAD</div>
                <table class="stats-table">
                    <tr>
                        <td class="stat-success">
                            <span class="stat-number">' . $estadisticas['productos_alta_rentabilidad'] . '</span>
                            <span class="stat-label">Alta Rentabilidad</span>
                        </td>
                        <td class="stat-warning">
                            <span class="stat-number">' . $estadisticas['productos_media_rentabilidad'] . '</span>
                            <span class="stat-label">Media Rentabilidad</span>
                        </td>
                        <td class="stat-danger">
                            <span class="stat-number">' . $estadisticas['productos_baja_rentabilidad'] . '</span>
                            <span class="stat-label">Baja Rentabilidad</span>
                        </td>
                        <td>
                            <span class="stat-number">' . $estadisticas['margen_promedio'] . '%</span>
                            <span class="stat-label">Margen Promedio</span>
                        </td>
                    </tr>
                </table>
                
                <table class="stats-table">
                    <tr>
                        <td class="stat-success">
                            <span class="stat-number">$' . $estadisticas['ganancia_total_proyectada'] . '</span>
                            <span class="stat-label">Ganancia Total</span>
                        </td>
                        <td class="stat-danger">
                            <span class="stat-number">$' . $estadisticas['ganancia_perdida_desperdicio'] . '</span>
                            <span class="stat-label">Perdida en Desperdicio</span>
                        </td>
                        <td>
                            <span class="stat-number">' . $estadisticas['producto_mas_rentable'] . '</span>
                            <span class="stat-label">Más Rentable</span>
                        </td>
                        <td>
                            <span class="stat-number">' . $estadisticas['margen_mas_rentable'] . '</span>
                            <span class="stat-label">Margen Más Alto</span>
                        </td>
                        <!--<td>
                            <span class="stat-number">' . $estadisticas['producto_menos_rentable'] . '</span>
                            <span class="stat-label">Menos Rentable</span>
                        </td>-->
                    </tr>
                </table>
            </div>';
            
        if ($productos->count() > 0) {
            $html .= '
            <div class="table-container">
                <div class="table-title">DETALLE DE RENTABILIDAD POR PRODUCTO</div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-right">P. Compra(Kg)</th>
                            <th class="text-right">P. Venta(Kg)</th>
                            <th class="text-right">Margen $</th>
                            <th class="text-right">Margen %</th>
                            <th class="text-right">Stock Neto</th>
                            <th class="text-right">Ganancia Total</th>
                            <th class="text-center">Rentabilidad</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($productos as $index => $producto) {
                // Determinar clase por nivel de rentabilidad
                $claseFila = '';
                $badgeClase = '';
                $rentabilidadTexto = '';
                
                switch ($producto->rentabilidad) {
                    case 'alta':
                        $claseFila = 'alta-rentabilidad';
                        $badgeClase = 'badge-success';
                        $rentabilidadTexto = 'ALTA';
                        break;
                    case 'media':
                        $claseFila = 'media-rentabilidad';
                        $badgeClase = 'badge-warning';
                        $rentabilidadTexto = 'MEDIA';
                        break;
                    case 'baja':
                        $claseFila = 'baja-rentabilidad';
                        $badgeClase = 'badge-danger';
                        $rentabilidadTexto = 'BAJA';
                        break;
                }
                
                $html .= '
                    <tr class="' . $claseFila . '">
                        <td>' . ($index + 1) . '</td>
                        <td>' . htmlspecialchars($producto->nombre) . '</td>
                        <td>' . htmlspecialchars($producto->categoria->nombre ?? 'Sin categoría') . '</td>
                        <td class="text-right">$' . number_format($producto->precio_compra, 2) . '</td>
                        <td class="text-right">$' . number_format($producto->precio_venta_kg, 2) . '</td>
                        <td class="text-right">$' . number_format($producto->margen_absoluto, 2) . '</td>
                        <td class="text-right">' . number_format($producto->margen_porcentual, 1) . '%</td>
                        <td class="text-right">' . number_format($producto->kilos_netos, 2) . ' kg</td>
                        <td class="text-right">$' . number_format($producto->ganancia_total, 2) . '</td>
                        <td class="text-center">
                            <span class="badge ' . $badgeClase . '">' . $rentabilidadTexto . '</span>
                        </td>
                    </tr>';
            }
            
            // Totales
            $totalGanancia = $productos->sum('ganancia_total');
            $totalStockNeto = $productos->sum('kilos_netos');
            $margenPromedio = $productos->avg('margen_porcentual');
            
            $html .= '
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" class="text-right"><strong>PROMEDIOS Y TOTALES:</strong></td>
                        <td class="text-right"><strong>' . number_format($totalStockNeto, 2) . ' kg</strong></td>
                        <td class="text-right"><strong>$' . number_format($totalGanancia, 2) . '</strong></td>
                        <td class="text-center"><strong>' . number_format($margenPromedio, 1) . '%</strong></td>
                    </tr>
                </tfoot>
            </table></div>';
        } else {
            $html .= '<div style="text-align: center; padding: 20px 15px; background: #f0fdf4; border-radius: 6px; border: 1px solid #bbf7d0;">
                <p style="color: #666; margin: 0; font-size: 9px;">No hay productos para analizar.</p>
            </div>';
        }
        
        $html .= '
            <div class="footer">
                Documento generado automáticamente por ' . $empresa . ' | Fecha: ' . $fechaVenezuela . ' (Venezuela)
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Métodos auxiliares
     */
    private function generarTextoFiltros($filters)
    {
        $texto = '';
        
        if (!empty($filters['categoria_id'])) {
            $categoria = Categoria::find($filters['categoria_id']);
            if ($categoria) {
                $texto .= '<strong>Categoría:</strong> ' . $categoria->nombre . ' | ';
            }
        }
        
        if (!empty($filters['proveedor_id'])) {
            $proveedor = Proveedores::find($filters['proveedor_id']);
            if ($proveedor) {
                $texto .= '<strong>Proveedor:</strong> ' . $proveedor->nombre . ' | ';
            }
        }
        
        if (!empty($filters['estado_stock'])) {
            $texto .= '<strong>Estado Stock:</strong> ' . $filters['estado_stock'] . ' | ';
        }
        
        if ($texto) {
            $texto = rtrim($texto, ' | ');
        }
        
        return $texto;
    }

    private function parseFiltros($filters)
    {
        $filtrosParsed = [];
        
        if (!empty($filters['categoria_id'])) {
            $categoria = Categoria::find($filters['categoria_id']);
            $filtrosParsed['Categoría'] = $categoria ? $categoria->nombre : 'Desconocida';
        }
        
        if (!empty($filters['proveedor_id'])) {
            $proveedor = Proveedores::find($filters['proveedor_id']);
            $filtrosParsed['Proveedor'] = $proveedor ? $proveedor->nombre : 'Desconocido';
        }
        
        return $filtrosParsed;
    }
    
    private function clasificarRentabilidad($margen)
    {
        if ($margen >= 50) return 'alta';
        if ($margen >= 20) return 'media';
        return 'baja';
    }
    
    private function determinarEstadoStock($kilogramos)
    {
        if ($kilogramos <= 10) return 'bajo';
        if ($kilogramos <= 20) return 'medio';
        return 'normal';
    }


}