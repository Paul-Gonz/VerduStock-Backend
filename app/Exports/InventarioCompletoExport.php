<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class InventarioCompletoExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    WithTitle,
    WithColumnWidths,
    WithEvents
{
    protected $productos;
    protected $estadisticas;
    protected $filters;

    public function __construct($productos, $estadisticas, $filters)
    {
        $this->productos = $productos;
        $this->estadisticas = $estadisticas;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->productos;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Producto',
            'Categoría',
            'Proveedor',
            'Stock Bruto (kg)',
            'Desperdicio (kg)',
            'Stock Neto (kg)',
            'Precio Compra',
            'Precio Venta',
            'Ganancia/kg',
            'Ganancia Total',
            '% Desperdicio',
            'Estado Stock',
            'Fecha Registro'
        ];
    }

    public function map($producto): array
    {
        $desperdicio = $producto->desperdicio ?? 0;
        $kilosNetos = $producto->kilogramos - $desperdicio;
        $gananciaPorKg = $producto->precio_venta_kg - $producto->precio_compra;
        $gananciaTotal = $gananciaPorKg * $kilosNetos;
        $porcentajeDesperdicio = $producto->kilogramos > 0 ? ($desperdicio / $producto->kilogramos) * 100 : 0;
        
        $estadoStock = 'Normal';
        if ($kilosNetos <= 10) {
            $estadoStock = 'CRÍTICO';
        } elseif ($kilosNetos <= 20) {
            $estadoStock = 'Medio';
        }
        
        return [
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

    /**
     * Configurar anchos de columna
     */
    public function columnWidths(): array
    {
        return [
            'A' => 5,      // ID
            'B' => 25,     // Producto (más ancho)
            'C' => 20,     // Categoría
            'D' => 25,     // Proveedor (más ancho)
            'E' => 8,     // Stock Bruto
            'F' => 8,     // Desperdicio
            'G' => 8,     // Stock Neto
            'H' => 10,     // Precio Compra
            'I' => 10,     // Precio Venta
            'J' => 10,     // Ganancia/kg
            'K' => 11,     // Ganancia Total
            'L' => 10,     // % Desperdicio
            'M' => 12,     // Estado Stock
            'N' => 22,     // Fecha Registro
        ];
    }

    /**
     * Estilos y eventos adicionales
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la fila de encabezados (fila 1)
            1 => [
                'font' => [
                    'bold' => true, 
                    'size' => 12,  // Aumentado de 11 a 12
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '22C55E']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true  // Permitir texto en múltiples líneas
                ]
            ],
        ];
    }

    /**
     * Eventos post-creación para ajustes adicionales
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Ajustar altura de la fila de encabezados
                $event->sheet->getRowDimension(1)->setRowHeight(25);  // Altura aumentada a 25
                
                // Aplicar negrita a la primera fila
                $event->sheet->getStyle('A1:N1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ]
                ]);
                
                // Centrar verticalmente todas las celdas
                $event->sheet->getStyle('A1:N' . ($event->sheet->getHighestRow()))->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                
                // Ajustar automáticamente el alto de las filas según el contenido
                $highestRow = $event->sheet->getHighestRow();
                for ($row = 2; $row <= $highestRow; $row++) {
                    $event->sheet->getRowDimension($row)->setRowHeight(-1); // Autoajuste
                }
                
                // Aplicar formato de moneda a las columnas de precios
                $columns = ['H', 'I', 'J', 'K'];
                foreach ($columns as $col) {
                    $event->sheet->getStyle($col . '2:' . $col . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('"$"#,##0.00');
                }
                
                // Formato de porcentaje para columna L
                $event->sheet->getStyle('L2:L' . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('0.0"% "');
                
                // Colorear filas según estado crítico
                $lastRow = $event->sheet->getHighestRow();
                for ($row = 2; $row <= $lastRow; $row++) {
                    $estado = $event->sheet->getCell('M' . $row)->getValue();
                    if ($estado == 'CRÍTICO') {
                        $event->sheet->getStyle("A{$row}:N{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FEE2E2');  // Rojo claro
                        $event->sheet->getStyle("M{$row}")->getFont()
                            ->setColor(new Color('DC2626'))->setBold(true);
                    } elseif ($estado == 'Medio') {
                        $event->sheet->getStyle("A{$row}:N{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FEF3C7');  // Amarillo claro
                    }
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Inventario Completo';
    }
}