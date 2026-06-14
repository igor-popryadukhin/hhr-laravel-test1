<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Карточка организации в Яндекс.Картах.
 *
 * parse_status — машина состояний парсинга:
 *  - pending   → URL сохранили, парсер ещё не запускали
 *  - parsing   → парсер в работе (пользователь видит спиннер)
 *  - completed → данные загружены
 *  - failed    → что-то пошло не так (parse_error содержит текст ошибки)
 */
class Organization extends Model
{
    protected $fillable = [
        'user_id', 'yandex_url', 'yandex_org_id', 'name', 'address',
        'average_rating', 'rating_count', 'review_count',
        'parse_status', 'parse_error', 'parsed_at',
    ];

    protected function casts(): array
    {
        return [
            'average_rating' => 'float',
            'rating_count' => 'integer',
            'review_count' => 'integer',
            'parsed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
