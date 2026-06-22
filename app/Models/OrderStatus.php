<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatus extends Model
{
    protected $table = 'order_status';
    protected $fillable = [
        'id',
        'description',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'status_id', 'id');
    }
}
