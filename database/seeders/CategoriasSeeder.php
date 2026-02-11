<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriasSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Frutas', 'detalle' => 'Frutas frescas', 'emoji' => '🍎'],
            ['nombre' => 'Hojas verdes', 'detalle' => 'Vegetales de hoja', 'emoji' => '🥬'],
            ['nombre' => 'Tubérculos', 'detalle' => 'Tubérculos como papa y batata', 'emoji' => '🥔'],
            ['nombre' => 'Frutos de huerta', 'detalle' => 'Tomate, pepino y similares', 'emoji' => '🍅'],
            ['nombre' => 'Raíces', 'detalle' => 'Raíces comestibles', 'emoji' => '🥕'],
            ['nombre' => 'Bulbos', 'detalle' => 'Cebolla, ajo y similares', 'emoji' => '🧅'],
            ['nombre' => 'Crucíferas', 'detalle' => 'Brócoli, coles y crucíferas', 'emoji' => '🥦'],
            ['nombre' => 'Hierbas aromáticas', 'detalle' => 'Perejil, albahaca, cilantro, etc.', 'emoji' => '🌿'],
            ['nombre' => 'Frutos secos', 'detalle' => 'Nueces, almendras y similares', 'emoji' => '🥜'],
        ];

        foreach ($categorias as $data) {
            Categoria::updateOrCreate(
                ['nombre' => $data['nombre']],
                $data
            );
        }
    }
}
