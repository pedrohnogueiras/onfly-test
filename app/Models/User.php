<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Helpers\AppHelper;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['api_key', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;

    public const PREFIX_EXTERNAL_REFERENCE = 'usr';

    protected $fillable = [
        'name', 'email', 'password', 'is_admin',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    public static function boot(): void
    {
        parent::boot();

        self::creating(function ($model) {
            $model->ref = AppHelper::createExternalReference(self::PREFIX_EXTERNAL_REFERENCE);
        });
    }

    public static function hashApiKey(string $apiKey): string
    {
        return hash_hmac('sha256', $apiKey, config('jwt.hash_salt'));
    }

    public function generateApiKey(): string
    {
        $key = Str::random(48);

        $this->api_key = self::hashApiKey($key);
        $this->save();

        return $key;
    }

    public static function findByApiKey(string $apiKey): ?self
    {
        return static::where('api_key', self::hashApiKey($apiKey))->first();
    }
}
