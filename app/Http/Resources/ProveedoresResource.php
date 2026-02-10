<?php 

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedoresResource extends JsonResource {

    public function toArray (Request $request): array {

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'detalle' => $this->detalle,
            
            // Estado del Soft Delete
            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toIso8601String() : null,

            // Formateo de fechas consistente
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}