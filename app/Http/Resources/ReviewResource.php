<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON-представление одного отзыва.
 *
 * Ничего лишнего — только то что нужно для карточки в SPA:
 * автор, аватар, оценка, текст, дата.
 */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author_name' => $this->author_name,
            'author_avatar' => $this->author_avatar,
            'rating' => $this->rating,
            'text' => $this->text,
            'review_date' => $this->review_date?->format('Y-m-d'),
        ];
    }
}
