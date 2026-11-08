<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Opaque bearer-token session. Only a SHA-256 digest of the token is stored,
 * so a database leak does not reveal usable tokens. Mirrors the original
 * `sessions` table but renamed to avoid clashing with Laravel's web sessions.
 */
class AuthToken extends Model
{
    use UsesStringId;

    protected $fillable = [
        'id',
        'user_id',
        'token_hash',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function digest(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Resolve a bearer token to its (non-expired) user, or null.
     */
    public static function resolveUser(?string $token): ?User
    {
        if (! $token) {
            return null;
        }

        $record = static::query()
            ->where('token_hash', static::digest($token))
            ->first();

        if (! $record) {
            return null;
        }

        if ($record->expires_at->isPast()) {
            $record->delete();

            return null;
        }

        return User::find($record->user_id);
    }

    /**
     * Issue a new token for a user and return its raw value + expiry.
     */
    public static function issue(User $user, int $ttlHours = 168): array
    {
        $token = Str::random(64);
        $expiresAt = now()->addHours($ttlHours);

        static::query()->create([
            'user_id' => $user->id,
            'token_hash' => static::digest($token),
            'expires_at' => $expiresAt,
        ]);

        return ['token' => $token, 'expiresAt' => $expiresAt->toIso8601String()];
    }

    public static function revoke(string $token): void
    {
        if ($token) {
            static::query()->where('token_hash', static::digest($token))->delete();
        }
    }
}
