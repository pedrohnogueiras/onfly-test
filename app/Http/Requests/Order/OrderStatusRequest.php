<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    OrderStatusEnum::Approved->value,
                    OrderStatusEnum::Cancelled->value,
                ]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'Status inválido. Use '
                . OrderStatusEnum::Approved->value . ' (Aprovado) ou '
                . OrderStatusEnum::Cancelled->value . ' (Cancelado).',
        ];
    }
}
