<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\Order\Request\OrderDestinationRequestDTO;
use App\DTO\Order\Request\OrderRequestDTO;
use App\Enums\OrderStatusEnum;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class OrderRequestDTOTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function baseDestinationInput(): array
    {
        return [
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'pais' => 'Brasil',
        ];
    }

    private function baseOrderInput(array $overrides = []): array
    {
        return array_merge([
            'user_id' => 42,
            'solicitante' => 'Jane Doe',
            'data_partida' => '01-03-2026',
            'data_retorno' => '10-03-2026',
            'destino' => $this->baseDestinationInput(),
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // OrderRequestDTO — input → internal property mapping
    // -------------------------------------------------------------------------

    public function test_maps_user_id_to_userId(): void
    {
        $dto = OrderRequestDTO::from($this->baseOrderInput());

        $this->assertSame(42, $dto->userId);
    }

    public function test_maps_solicitante_to_userName(): void
    {
        $dto = OrderRequestDTO::from($this->baseOrderInput());

        $this->assertSame('Jane Doe', $dto->userName);
    }

    public function test_maps_data_partida_to_departureDate(): void
    {
        $dto = OrderRequestDTO::from($this->baseOrderInput());

        $this->assertInstanceOf(Carbon::class, $dto->departureDate);
        $this->assertSame('2026-03-01', $dto->departureDate->format('Y-m-d'));
    }

    public function test_maps_data_retorno_to_returnDate(): void
    {
        $dto = OrderRequestDTO::from($this->baseOrderInput());

        $this->assertInstanceOf(Carbon::class, $dto->returnDate);
        $this->assertSame('2026-03-10', $dto->returnDate->format('Y-m-d'));
    }

    public function test_maps_destino_to_orderDestination(): void
    {
        $dto = OrderRequestDTO::from($this->baseOrderInput());

        $this->assertInstanceOf(OrderDestinationRequestDTO::class, $dto->orderDestination);
    }

    // -------------------------------------------------------------------------
    // OrderRequestDTO — default status
    // -------------------------------------------------------------------------

    public function test_status_defaults_to_registred_when_omitted(): void
    {
        $dto = OrderRequestDTO::from($this->baseOrderInput());

        $this->assertSame(OrderStatusEnum::Registred, $dto->orderStatus);
    }

    public function test_status_defaults_to_registred_when_explicitly_null(): void
    {
        $dto = OrderRequestDTO::from($this->baseOrderInput(['status' => null]));

        $this->assertSame(OrderStatusEnum::Registred, $dto->orderStatus);
    }

    public function test_status_is_set_when_provided(): void
    {
        $dto = OrderRequestDTO::from($this->baseOrderInput(['status' => 2]));

        $this->assertSame(OrderStatusEnum::Approved, $dto->orderStatus);
    }

    // -------------------------------------------------------------------------
    // OrderRequestDTO — toArray output keys
    // -------------------------------------------------------------------------

    public function test_toArray_uses_output_mapped_keys(): void
    {
        $dto = OrderRequestDTO::from($this->baseOrderInput());
        $output = $dto->toArray();

        $this->assertArrayHasKey('user_id', $output);
        $this->assertArrayHasKey('applicant', $output);
        $this->assertArrayHasKey('departure_date', $output);
        $this->assertArrayHasKey('return_date', $output);
        $this->assertArrayHasKey('destination', $output);
        $this->assertArrayHasKey('status_id', $output);
    }

    public function test_toArray_output_values_are_correct(): void
    {
        $dto = OrderRequestDTO::from($this->baseOrderInput());
        $output = $dto->toArray();

        $this->assertSame(42, $output['user_id']);
        $this->assertSame('Jane Doe', $output['applicant']);
        $this->assertSame('2026-03-01', $output['departure_date']);
        $this->assertSame('2026-03-10', $output['return_date']);
        $this->assertSame(OrderStatusEnum::Registred->value, $output['status_id']);
    }

    // -------------------------------------------------------------------------
    // OrderDestinationRequestDTO — input → internal property mapping
    // -------------------------------------------------------------------------

    public function test_destination_maps_cidade_to_city(): void
    {
        $dto = OrderDestinationRequestDTO::from($this->baseDestinationInput());

        $this->assertSame('São Paulo', $dto->city);
    }

    public function test_destination_maps_estado_to_state(): void
    {
        $dto = OrderDestinationRequestDTO::from($this->baseDestinationInput());

        $this->assertSame('SP', $dto->state);
    }

    /**
     * The internal property is $county (typo in production code).
     * MapOutputName("country") ensures the serialised key is "country".
     * This test documents the real behaviour: pais → $county, not $country.
     */
    public function test_destination_maps_pais_to_county_internal_property(): void
    {
        $dto = OrderDestinationRequestDTO::from($this->baseDestinationInput());

        $this->assertSame('Brasil', $dto->county);
    }

    public function test_destination_toArray_exposes_country_key_despite_typo(): void
    {
        $dto = OrderDestinationRequestDTO::from($this->baseDestinationInput());
        $output = $dto->toArray();

        $this->assertArrayHasKey('country', $output);
        $this->assertArrayNotHasKey('county', $output);
        $this->assertSame('Brasil', $output['country']);
    }

    // -------------------------------------------------------------------------
    // OrderDestinationRequestDTO nested inside OrderRequestDTO
    // -------------------------------------------------------------------------

    public function test_nested_destination_output_contains_country_key(): void
    {
        $dto = OrderRequestDTO::from($this->baseOrderInput());
        $output = $dto->toArray();

        $this->assertIsArray($output['destination']);
        $this->assertArrayHasKey('country', $output['destination']);
        $this->assertSame('Brasil', $output['destination']['country']);
    }
}
