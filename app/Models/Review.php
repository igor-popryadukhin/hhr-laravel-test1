<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Один отзыв с Яндекс.Карт.
 *
 * yandex_review_id — уникальный идентификатор отзыва в Яндексе.
 * По нему делаем upsert при повторном парсинге:
 * что было — обновится, что новое — добавится, дублей не будет.
 */
class Review extends Model
{
    protected $fillable = [
        'organization_id', 'yandex_review_id', 'author_name',
        'author_avatar', 'rating', 'text', 'review_date',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'review_date' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
