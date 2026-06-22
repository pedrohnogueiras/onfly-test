<?php

declare(strict_types=1);

namespace App\Logging;

use App\DTO\Request\RequestInfoData;
use Illuminate\Support\Facades\Log;

/**
 * Logger encapsulado e reutilizável por qualquer componente (controllers, services, jobs...).
 *
 * Defina o contexto uma vez com setContext() e use info()/debug()/error() para registrar
 * mensagens já enriquecidas com context, request_id e user_ref.
 */
class RequestLogger
{
    public const INFO = 'info';
    public const DEBUG = 'debug';
    public const ERROR = 'error';

    private ?RequestInfoData $info = null;

    /**
     * Define os dados de contexto (context, request_id, user_ref) aplicados a todos os logs seguintes.
     */
    public function setContext(array $data): self
    {
        $this->info = RequestInfoData::from($data);

        return $this;
    }

    public function info(?string $action = null, ?string $message = null, array $context = []): void
    {
        $this->write(self::INFO, $action, $message, $context);
    }

    public function debug(?string $action = null, ?string $message = null, array $context = []): void
    {
        $this->write(self::DEBUG, $action, $message, $context);
    }

    public function error(?string $action = null, ?string $message = null, array $context = []): void
    {
        $this->write(self::ERROR, $action, $message, $context);
    }

    public function write(string $level, ?string $action = null, ?string $message = null, array $context = []): void
    {
        Log::log(
            $level,
            ($this->info?->context ?? '') . ' - ' . $action,
            array_merge([
                'request_id' => $this->info?->request_id ?? '',
                'user_ref' => $this->info?->user_ref ?? '',
                'message' => $message,
            ], $context),
        );
    }
}
