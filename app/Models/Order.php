<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\AppHelper;
use Carbon\Carbon;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const PREFIX_EXTERNAL_REFERENCE = 'ped';

    protected $fillable = [
        'user_id',
        'applicant',
        'departure_date',
        'return_date',
        'status_id',
    ];

    protected $casts = [];

    protected $hidden = [
        'updated_at',
        'deleted_at',
        'id',
        'user_id',
    ];

    public static function boot(): void
    {
        parent::boot();

        self::creating(function ($model) {
            $model->ref = AppHelper::createExternalReference(self::PREFIX_EXTERNAL_REFERENCE);
        });
    }

    /**
     * Normaliza departure_date para Y-m-d ao persistir.
     * Aceita Carbon, string d-m-Y ou string Y-m-d.
     * Lê o valor do banco como string Y-m-d.
     */
    protected function departureDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value,
            set: fn (Carbon|string|null $value) => $this->normalizeDate($value),
        );
    }

    /**
     * Normaliza return_date para Y-m-d ao persistir.
     * Aceita Carbon, string d-m-Y ou string Y-m-d.
     * Lê o valor do banco como string Y-m-d.
     */
    protected function returnDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value,
            set: fn (Carbon|string|null $value) => $this->normalizeDate($value),
        );
    }

    /**
     * Converte data de qualquer formato suportado para Y-m-d.
     * Aceita instância Carbon, string d-m-Y ou string Y-m-d.
     */
    private function normalizeDate(Carbon|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        // Detecta formato d-m-Y (ex: 10-07-2027)
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
            return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
        }

        // Já está em Y-m-d ou formato ISO → retorna como está
        return $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id', 'id');
    }

    public function destination(): HasOne
    {
        return $this->hasOne(Destination::class, 'order_id', 'id');
    }
}
