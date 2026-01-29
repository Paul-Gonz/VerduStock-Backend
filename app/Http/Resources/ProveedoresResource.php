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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

}