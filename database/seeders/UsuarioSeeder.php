<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Para PostgreSQL - deshabilitar triggers de clave foránea
        Schema::disableForeignKeyConstraints();
        Usuario::truncate();
        Schema::enableForeignKeyConstraints();

        // Otra opción (más manual):
        // DB::statement('ALTER TABLE usuarios DISABLE TRIGGER ALL;');
        // Usuario::truncate();
        // DB::statement('ALTER TABLE usuarios ENABLE TRIGGER ALL;');

        $usuarios = [
            [
                'nombre' => 'admin',
                'password' => Hash::make('admin123'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
          
        ];

        // Insertar usuarios
        foreach ($usuarios as $usuario) {
            Usuario::create($usuario);
        }

        // Mostrar mensaje en consola
        $this->command->info('Se han insertado ' . count($usuarios) . ' usuarios en la base de datos.');
    }
}