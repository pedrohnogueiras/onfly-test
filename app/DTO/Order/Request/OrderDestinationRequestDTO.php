<?php

declare(strict_types=1);

namespace App\DTO\Order\Request;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

class OrderDestinationRequestDTO extends Data
{
    public function __construct(
        #[MapInputName('cidade')]
        #[MapOutputName('city')]
        public string $city,
        #[MapInputName('estado')]
        #[MapOutputName('state')]
        public string $state,
        #[MapInputName('pais')]
        #[MapOutputName('country')]
        public string $county,
    ) {
    }
}
