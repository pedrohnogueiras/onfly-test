<?php

declare(strict_types=1);

namespace App\DTO\Order\Request;

use App\Enums\OrderStatusEnum;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class OrderFilterDTO extends Data
{
    public function __construct(
        #[MapInputName('status')]
        public readonly ?OrderStatusEnum $status,
        #[MapInputName('data_inicio')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'd-m-Y', type: Carbon::class)]
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        public readonly ?Carbon $dataInicio,
        #[MapInputName('data_fim')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'd-m-Y', type: Carbon::class)]
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        public readonly ?Carbon $dataFim,
    ) {
    }
}
