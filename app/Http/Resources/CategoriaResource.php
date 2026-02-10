<?php
// app/Http/Resources/CategoriaResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'detalle' => $this->detalle,
            'emoji' => $this->emoji,
            
            // Estado del Soft Delete para el Frontend
            'is_deleted' => $this->trashed(), 
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toIso8601String() : null,

            // Formatos ISO para Nuxt/JS
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            
            // Relaciones futuras seguras:
            'productos_count' => $this->whenCounted('productos'),
        ];
    }
}