<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\OrderStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class OrderStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'solicitante' => ['required','string'],
            'data_partida' => ['required','date','date_format:d-m-Y'],
            'data_retorno' => ['required','date','date_format:d-m-Y','after_or_equal:data_partida'],
            'destino' => ['required'],
            'destino.cidade' => ['required','string'],
            'destino.estado' => ['required','string'],
            'destino.pais' => ['required','string'],
            'status' => [new Enum(OrderStatusEnum::class)],

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
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser uma string.',
            'numeric' => 'O campo :attribute deve ser numérico',
            'date' => 'O campo :attribute deve ser uma data válida.',
            'date_format' => 'O campo :attribute deve estar com a data no formato: DD-MM-YYYY',
            'after_or_equal' => 'O campo :attribute deve ter data igual ou superior a :date',
            'status.' . Enum::class => "O status selecionado é inválido. Opções permitidas: {$opcoes}.",
            'status.numeric' => 'O campo status deve ser um número.',
            'destino.array' => 'O campo destino deve ser uma lista de itens.',
        ];

    }

    public function attributes(): array
    {
        return [
            'solicitante' => 'nome do solicitante',
            'data_retorno' => 'data de retorno',
            'data_partida' => 'data de partida',
            'status' => 'status do pedido',
            'destino.cidade' => 'cidade do destino',
            'destino.estado' => 'estado do destino',
            'destino.pais' => 'país do destino',
        ];
    }

}
