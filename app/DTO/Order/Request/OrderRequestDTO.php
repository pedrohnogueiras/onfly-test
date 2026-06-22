<?php

declare(strict_types=1);

namespace App\DTO\Order\Request;

use App\Enums\OrderStatusEnum;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class OrderRequestDTO extends Data
{
    public function __construct(
        #[MapInputName('user_id')]
        #[MapOutputName('user_id')]
        public int $userId,
        #[MapInputName('solicitante')]
        #[MapOutputName('applicant')]
        public string $userName,
        #[MapInputName('data_partida')]
        #[MapOutputName('departure_date')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'd-m-Y', type: Carbon::class)]
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        public Carbon $departureDate,
        #[MapInputName('data_retorno')]
        #[MapOutputName('return_date')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'd-m-Y', type: Carbon::class)]
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        public Carbon $returnDate,
        #[MapInputName('destino')]
        #[MapOutputName('destination')]
        public OrderDestinationRequestDTO $orderDestination,
        #[MapInputName('status')]
        #[MapOutputName('status_id')]
        public ?OrderStatusEnum $orderStatus,
    ) {

        $this->orderStatus = $orderStatus ?? OrderStatusEnum::Registred;

    }
}
