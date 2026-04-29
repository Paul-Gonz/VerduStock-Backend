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

class DesperdiciosExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithColumnWidths, WithStyles, WithEvents
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
            'Stock Bruto (kg)',
            'Desperdicio (kg)',
            '% Desperdicio',
            'Precio Venta/kg',
            'Ganancia/kg',
            'Pérdida por Desperdicio',
            'Nivel'
        ];
    }

    public function map($producto): array
    {
        $desperdicio = $producto->desperdicio ?? 0;
        $porcentaje = $producto->kilogramos > 0 ? ($desperdicio / $producto->kilogramos) * 100 : 0;
        $gananciaPorKg = $producto->precio_venta_kg - $producto->precio_compra;
        $gananciaPerdida = $desperdicio * $gananciaPorKg;
        
        $nivel = 'BAJO';
        if ($porcentaje > 20) {
            $nivel = 'ALTO';
        } elseif ($porcentaje > 5) {
            $nivel = 'MODERADO';
        }
        
        return [
            $producto->id,
            $producto->nombre,
            $producto->categoria->nombre ?? 'Sin categoría',
            number_format($producto->kilogramos, 2),
            number_format($desperdicio, 2),
            number_format($porcentaje, 1) . '%',
            '$' . number_format($producto->precio_venta_kg, 2),
            '$' . number_format($gananciaPorKg, 2),
            '$' . number_format($gananciaPerdida, 2),
            $nivel
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,      // ID
            'B' => 35,     // Producto
            'C' => 25,     // Categoría
            'D' => 18,     // Stock Bruto
            'E' => 18,     // Desperdicio
            'F' => 16,     // % Desperdicio
            'G' => 20,     // Precio Venta/kg
            'H' => 18,     // Ganancia/kg
            'I' => 25,     // Pérdida por Desperdicio
            'J' => 15,     // Nivel
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EA580C']  // Naranja para desperdicios
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
                $event->sheet->getStyle('A1:J' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1']
                        ]
                    ]
                ]);
                
                // Formato de números y moneda
                $event->sheet->getStyle('G2:G' . $lastRow)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');
                $event->sheet->getStyle('H2:H' . $lastRow)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');
                $event->sheet->getStyle('I2:I' . $lastRow)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');
                
                // Colorear según nivel de desperdicio
                for ($row = 2; $row <= $lastRow; $row++) {
                    $nivel = $event->sheet->getCell('J' . $row)->getValue();
                    $porcentaje = floatval($event->sheet->getCell('F' . $row)->getValue());
                    
                    if ($nivel == 'ALTO' || $porcentaje > 20) {
                        // Rojo intenso para alto desperdicio
                        $event->sheet->getStyle("A{$row}:J{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FEE2E2');
                        $event->sheet->getStyle("F{$row}:J{$row}")->getFont()
                            ->setColor(new Color('DC2626'))->setBold(true);
                    } elseif ($nivel == 'MODERADO' || $porcentaje > 5) {
                        // Amarillo para moderado
                        $event->sheet->getStyle("A{$row}:J{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FEF3C7');
                        $event->sheet->getStyle("F{$row}:J{$row}")->getFont()
                            ->setColor(new Color('D97706'))->setBold(true);
                    } else {
                        // Verde claro para bajo desperdicio
                        $event->sheet->getStyle("A{$row}:J{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('DCFCE7');
                    }
                }
                
                // Agregar fila de resumen con estadísticas
                $summaryRow = $lastRow + 2;
                $event->sheet->setCellValue("A{$summaryRow}", "RESUMEN:");
                $event->sheet->getStyle("A{$summaryRow}")->getFont()->setBold(true);
                $event->sheet->mergeCells("A{$summaryRow}:C{$summaryRow}");
                
                $event->sheet->setCellValue("D{$summaryRow}", "Total Desperdicio:");
                $event->sheet->setCellValue("E{$summaryRow}", $this->estadisticas['total_desperdicio_kg'] . " kg");
                $event->sheet->getStyle("E{$summaryRow}")->getFont()->setBold(true)->setColor(new Color('DC2626'));
                
                $event->sheet->setCellValue("F{$summaryRow}", "Pérdida Total:");
                $event->sheet->setCellValue("G{$summaryRow}", "$" . number_format($this->estadisticas['ganancia_perdida'], 2));
                $event->sheet->getStyle("G{$summaryRow}")->getFont()->setBold(true)->setColor(new Color('DC2626'));
                
                $event->sheet->setCellValue("H{$summaryRow}", "Productos Afectados:");
                $event->sheet->setCellValue("I{$summaryRow}", $this->estadisticas['total_productos']);
                $event->sheet->getStyle("I{$summaryRow}")->getFont()->setBold(true);
                
                // Aplicar formato al resumen
                $event->sheet->getStyle("A{$summaryRow}:J{$summaryRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F0FDF4');
                $event->sheet->getStyle("A{$summaryRow}:J{$summaryRow}")->getBorders()
                    ->getTop()->setBorderStyle(Border::BORDER_THICK);
            },
        ];
    }

    public function title(): string
    {
        return 'Desperdicios';
    }
}