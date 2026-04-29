<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RentabilidadExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithColumnWidths, WithStyles, WithEvents
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
            'Producto',
            'Categoría',
            'Precio Compra (kg)',
            'Precio Venta (kg)',
            'Margen $',
            'Margen %',
            'Stock Neto (kg)',
            'Ganancia Total',
            'Rentabilidad'
        ];
    }

    public function map($producto): array
    {
        $rentabilidadTexto = 'BAJA';
        $rentabilidadColor = '';
        
        if ($producto->rentabilidad === 'alta') {
            $rentabilidadTexto = 'ALTA';
        } elseif ($producto->rentabilidad === 'media') {
            $rentabilidadTexto = 'MEDIA';
        }
        
        return [
            $producto->nombre,
            $producto->categoria->nombre ?? 'Sin categoría',
            '$' . number_format($producto->precio_compra, 2),
            '$' . number_format($producto->precio_venta_kg, 2),
            '$' . number_format($producto->margen_absoluto, 2),
            number_format($producto->margen_porcentual, 1) . '%',
            number_format($producto->kilos_netos, 2) . ' kg',
            '$' . number_format($producto->ganancia_total, 2),
            $rentabilidadTexto
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,     // Producto
            'B' => 25,     // Categoría
            'C' => 20,     // Precio Compra
            'D' => 20,     // Precio Venta
            'E' => 18,     // Margen $
            'F' => 15,     // Margen %
            'G' => 18,     // Stock Neto
            'H' => 22,     // Ganancia Total
            'I' => 18,     // Rentabilidad
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3B82F6']  // Azul moderno
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Altura de fila de encabezados
                $event->sheet->getRowDimension(1)->setRowHeight(25);
                
                // Bordes a toda la tabla
                $lastRow = $event->sheet->getHighestRow();
                $event->sheet->getStyle('A1:I' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1']
                        ]
                    ]
                ]);
                
                // Formato de números
                $event->sheet->getStyle('C2:C' . $lastRow)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');
                $event->sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');
                $event->sheet->getStyle('E2:E' . $lastRow)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');
                $event->sheet->getStyle('H2:H' . $lastRow)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');
                
                // Colorear según rentabilidad
                for ($row = 2; $row <= $lastRow; $row++) {
                    $rentabilidad = $event->sheet->getCell('I' . $row)->getValue();
                    
                    if ($rentabilidad == 'ALTA') {
                        // Verde claro para alta rentabilidad
                        $event->sheet->getStyle("A{$row}:I{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('DCFCE7');
                        $event->sheet->getStyle("I{$row}")->getFont()
                            ->setColor(new Color('166534'))->setBold(true);
                    } elseif ($rentabilidad == 'MEDIA') {
                        // Amarillo claro para media rentabilidad
                        $event->sheet->getStyle("A{$row}:I{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FEF3C7');
                        $event->sheet->getStyle("I{$row}")->getFont()
                            ->setColor(new Color('92400E'))->setBold(true);
                    } elseif ($rentabilidad == 'BAJA') {
                        // Rojo claro para baja rentabilidad
                        $event->sheet->getStyle("A{$row}:I{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FEE2E2');
                        $event->sheet->getStyle("I{$row}")->getFont()
                            ->setColor(new Color('991B1B'))->setBold(true);
                    }
                }
                
                // Alternar colores de filas (efecto zebra)
                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 == 0) {
                        // Filas pares sin color de fondo ya tienen color por rentabilidad
                        // Esto evita sobrescribir los colores de rentabilidad
                    }
                }
                
                // Título del reporte
                $event->sheet->mergeCells('A1:I1');
                $event->sheet->setCellValue('A1', 'ANÁLISIS DE RENTABILIDAD');
            },
        ];
    }

    public function title(): string
    {
        return 'Rentabilidad';
    }
}