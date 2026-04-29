<?php

namespace App\Exports;

use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ProductosExport implements 
    FromQuery,
    WithHeadings, 
    WithMapping, 
    WithStyles,
    WithColumnWidths,
    WithTitle,
    ShouldAutoSize,
    WithCustomStartCell,
    WithEvents
{
    protected $filters;
    protected $rowNumber = 0;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Producto::with(['categoria', 'proveedor']);
        
        // Aplicar filtros
        if (!empty($this->filters['categoria_id'])) {
            $query->where('categoria_id', $this->filters['categoria_id']);
        }
        
        if (!empty($this->filters['proveedor_id'])) {
            $query->where('proveedor_id', $this->filters['proveedor_id']);
        }
        
        if (!empty($this->filters['busqueda'])) {
            $query->where('nombre', 'like', '%' . $this->filters['busqueda'] . '%');
        }
        
        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            '#',
            'ID',
            'Producto',
            'Categoría',
            'Proveedor',
            'Stock Bruto (kg)',
            'Desperdicio (kg)',
            'Stock Neto (kg)',
            'Precio Compra (kg)',
            'Precio Venta (kg)',
            'Ganancia por kg',
            'Ganancia Total ($)',
            'Desperdicio %',
            'Estado Stock',
            'Fecha Registro'
        ];
    }

    public function map($producto): array
    {
        $this->rowNumber++;
        $desperdicio = $producto->desperdicio ?? 0;
        $kilosNetos = $producto->kilogramos - $desperdicio;
        $gananciaPorKg = $producto->precio_venta_kg - $producto->precio_compra;
        $gananciaTotal = $gananciaPorKg * $kilosNetos;
        $porcentajeDesperdicio = $producto->kilogramos > 0 ? ($desperdicio / $producto->kilogramos) * 100 : 0;
        
        // Determinar estado de stock
        $estadoStock = 'Normal';
        if ($kilosNetos <= 10) {
            $estadoStock = 'CRÍTICO';
        } elseif ($kilosNetos <= 20) {
            $estadoStock = 'Bajo';
        }
        
        return [
            $this->rowNumber,
            $producto->id,
            $producto->nombre,
            $producto->categoria->nombre ?? 'Sin categoría',
            $producto->proveedor->nombre ?? 'Sin proveedor',
            number_format($producto->kilogramos, 2),
            number_format($desperdicio, 2),
            number_format($kilosNetos, 2),
            '$' . number_format($producto->precio_compra, 2),
            '$' . number_format($producto->precio_venta_kg, 2),
            '$' . number_format($gananciaPorKg, 2),
            '$' . number_format($gananciaTotal, 2),
            number_format($porcentajeDesperdicio, 1) . '%',
            $estadoStock,
            $producto->created_at ? $producto->created_at->format('d/m/Y H:i') : 'N/A'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para encabezados
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '22C55E']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
            
            // Estilo para todas las celdas
            'A1:O' . ($sheet->getHighestRow()) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB']
                    ]
                ]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,      // #
            'B' => 8,      // ID
            'C' => 30,     // Producto
            'D' => 20,     // Categoría
            'E' => 25,     // Proveedor
            'F' => 18,     // Stock Bruto
            'G' => 18,     // Desperdicio
            'H' => 18,     // Stock Neto
            'I' => 18,     // Precio Compra
            'J' => 18,     // Precio Venta
            'K' => 18,     // Ganancia por kg
            'L' => 20,     // Ganancia Total
            'M' => 15,     // Desperdicio %
            'N' => 15,     // Estado Stock
            'O' => 20,     // Fecha
        ];
    }

    public function title(): string
    {
        return 'Reporte Inventario';
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Agregar título
                $event->sheet->mergeCells('A1:O1');
                $event->sheet->setCellValue('A1', 'REPORTE DE INVENTARIO - SISTEMA DE GESTIÓN');
                $event->sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true);
                $event->sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Agregar fecha de generación
                $event->sheet->mergeCells('A2:O2');
                $event->sheet->setCellValue('A2', 'Generado: ' . now()->format('d/m/Y H:i:s'));
                $event->sheet->getStyle('A2')->getFont()->setSize(9);
                $event->sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Colorear filas según nivel de desperdicio
                $highestRow = $event->sheet->getHighestRow();
                for ($row = 4; $row <= $highestRow; $row++) {
                    $porcentajeCelda = $event->sheet->getCell('M' . $row)->getValue();
                    $porcentaje = floatval(str_replace('%', '', $porcentajeCelda));
                    
                    if ($porcentaje > 20) {
                        $event->sheet->getStyle("A{$row}:O{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FEE2E2');
                    } elseif ($porcentaje > 5) {
                        $event->sheet->getStyle("A{$row}:O{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FEF3C7');
                    }
                    
                    // Colorear estado crítico
                    $estado = $event->sheet->getCell('N' . $row)->getValue();
                    if ($estado == 'CRÍTICO') {
                        $event->sheet->getStyle("N{$row}")->getFont()->setColor(new Color('DC2626'))->setBold(true);
                    } elseif ($estado == 'Bajo') {
                        $event->sheet->getStyle("N{$row}")->getFont()->setColor(new Color('D97706'))->setBold(true);
                    }
                }
                
                // Agregar totales al final
                $lastRow = $highestRow + 2;
                $totalStockBruto = $this->getTotalStockBruto();
                $totalDesperdicio = $this->getTotalDesperdicio();
                $totalStockNeto = $totalStockBruto - $totalDesperdicio;
                $totalGanancia = $this->getTotalGanancia();
                
                $event->sheet->setCellValue("C{$lastRow}", "TOTALES:");
                $event->sheet->getStyle("C{$lastRow}")->getFont()->setBold(true);
                $event->sheet->setCellValue("F{$lastRow}", number_format($totalStockBruto, 2) . " kg");
                $event->sheet->setCellValue("G{$lastRow}", number_format($totalDesperdicio, 2) . " kg");
                $event->sheet->setCellValue("H{$lastRow}", number_format($totalStockNeto, 2) . " kg");
                $event->sheet->setCellValue("L{$lastRow}", "$" . number_format($totalGanancia, 2));
                
                $event->sheet->getStyle("C{$lastRow}:O{$lastRow}")->getFont()->setBold(true);
                $event->sheet->getStyle("C{$lastRow}:O{$lastRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('DCFCE7');
            }
        ];
    }
    
    private function getTotalStockBruto()
    {
        $query = Producto::query();
        if (!empty($this->filters['categoria_id'])) {
            $query->where('categoria_id', $this->filters['categoria_id']);
        }
        if (!empty($this->filters['proveedor_id'])) {
            $query->where('proveedor_id', $this->filters['proveedor_id']);
        }
        return $query->sum('kilogramos');
    }
    
    private function getTotalDesperdicio()
    {
        $query = Producto::query();
        if (!empty($this->filters['categoria_id'])) {
            $query->where('categoria_id', $this->filters['categoria_id']);
        }
        if (!empty($this->filters['proveedor_id'])) {
            $query->where('proveedor_id', $this->filters['proveedor_id']);
        }
        return $query->sum('desperdicio');
    }
    
    private function getTotalGanancia()
    {
        $query = Producto::with([]);
        if (!empty($this->filters['categoria_id'])) {
            $query->where('categoria_id', $this->filters['categoria_id']);
        }
        if (!empty($this->filters['proveedor_id'])) {
            $query->where('proveedor_id', $this->filters['proveedor_id']);
        }
        
        $productos = $query->get();
        return $productos->sum(function($p) {
            $kilosNetos = $p->kilogramos - ($p->desperdicio ?? 0);
            return ($p->precio_venta_kg - $p->precio_compra) * $kilosNetos;
        });
    }
}