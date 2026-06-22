<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Spatie\LaravelData\Data;

class RequestInfoData extends Data
{
    public function __construct(
        public string $context,
        public string $request_id,
        public ?string $user_ref,
    ) {
    }
}
