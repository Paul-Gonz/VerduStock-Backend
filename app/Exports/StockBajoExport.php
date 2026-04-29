<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StockBajoExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithColumnWidths, WithEvents
{
    protected $productos;
    protected $estadisticas;
    protected $umbral;

    public function __construct($productos, $estadisticas, $umbral)
    {
        $this->productos = $productos;
        $this->estadisticas = $estadisticas;
        $this->umbral = $umbral;
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
            'Stock Neto (kg)',
            'Desperdicio (kg)',
            'Precio Compra',
            'Precio Venta',
            'Ganancia/kg',
            'Ganancia Total',
            'Estado'
        ];
    }

    public function map($producto): array
    {
        $kilosNetos = $producto->kilogramos - ($producto->desperdicio ?? 0);
        $gananciaPorKg = $producto->precio_venta_kg - $producto->precio_compra;
        $gananciaTotal = $gananciaPorKg * $kilosNetos;
        
        $estado = $kilosNetos <= ($this->umbral * 0.5) ? 'CRÍTICO' : 'ADVERTENCIA';
        
        return [
            $producto->id,
            $producto->nombre,
            $producto->categoria->nombre ?? 'Sin categoría',
            number_format($kilosNetos, 2),
            number_format($producto->desperdicio ?? 0, 2),
            '$' . number_format($producto->precio_compra, 2),
            '$' . number_format($producto->precio_venta_kg, 2),
            '$' . number_format($gananciaPorKg, 2),
            '$' . number_format($gananciaTotal, 2),
            $estado
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,      // ID
            'B' => 35,     // Producto
            'C' => 25,     // Categoría
            'D' => 18,     // Stock Neto
            'E' => 18,     // Desperdicio
            'F' => 18,     // Precio Compra
            'G' => 18,     // Precio Venta
            'H' => 18,     // Ganancia/kg
            'I' => 22,     // Ganancia Total
            'J' => 15,     // Estado
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getRowDimension(1)->setRowHeight(25);
                $event->sheet->getStyle('A1:J1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
                
                // Colorear filas críticas
                $lastRow = $event->sheet->getHighestRow();
                for ($row = 2; $row <= $lastRow; $row++) {
                    $estado = $event->sheet->getCell('J' . $row)->getValue();
                    if ($estado == 'CRÍTICO') {
                        $event->sheet->getStyle("A{$row}:J{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FEE2E2');
                    }
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Stock Bajo';
    }
}