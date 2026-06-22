<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\AppHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Destination extends Model
{
    use SoftDeletes;
    use HasFactory;

    public const PREFIX_EXTERNAL_REFERENCE = 'des';

    protected $fillable = [
        'order_id',
        'city',
        'state',
        'country',
    ];

    public static function boot(): void
    {
        parent::boot();

        self::creating(function ($model) {
            $model->ref = AppHelper::createExternalReference(self::PREFIX_EXTERNAL_REFERENCE);
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
