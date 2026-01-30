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
            'created_at' => $this->created_at->toIso8601String(), // Formato ISO
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Si quieres formato español:
            // 'created_at' => $this->created_at->format('d/m/Y H:i:s'),
            // 'updated_at' => $this->updated_at->format('d/m/Y H:i:s'),
            
            // Relaciones futuras (cuando las agregues):
            // 'productos_count' => $this->whenCounted('productos'),
            // 'productos' => ProductoResource::collection($this->whenLoaded('productos'))
        ];
    }
}