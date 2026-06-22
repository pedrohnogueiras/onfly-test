<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\OrderStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(OrderStatusEnum::class)],
            'data_inicio' => ['sometimes', 'nullable', 'date_format:d-m-Y'],
            'data_fim' => ['sometimes', 'nullable', 'date_format:d-m-Y', 'after_or_equal:data_inicio'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $opcoes = implode(', ', OrderStatusEnum::getCases());

        return [
            'status.enum' => "O status selecionado é inválido. Opções permitidas: {$opcoes}.",
            'data_inicio.date_format' => 'O campo :attribute deve estar no formato: DD-MM-YYYY',
            'data_fim.date_format' => 'O campo :attribute deve estar no formato: DD-MM-YYYY',
            'data_fim.after_or_equal' => 'O campo :attribute deve ter data igual ou superior a :date',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'status do pedido',
            'data_inicio' => 'data de início',
            'data_fim' => 'data de fim',
        ];
    }
}
