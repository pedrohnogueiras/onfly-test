<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ref' => $this->ref,
            'solicitante' => $this->applicant,
            'data_partida' => Carbon::parse($this->departure_date)->format('d-m-Y'),
            'data_retorno' => Carbon::parse($this->return_date)->format('d-m-Y'),
            'status' => $this->whenLoaded('orderStatus', fn () => $this->orderStatus->description),
            'destino' => $this->whenLoaded('destination', fn () => [
                'cidade' => $this->destination->city,
                'estado' => $this->destination->state,
                'pais' => $this->destination->country,
            ]),
            'criado_em' => $this->created_at,
        ];
    }
}
