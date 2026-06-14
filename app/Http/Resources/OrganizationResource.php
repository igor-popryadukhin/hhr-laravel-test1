<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON-представление организации для API.
 *
 * parse_status критичен для фронта:
 *  - 'parsing' → показываем спиннер, опрашиваем пока не завершится
 *  - 'completed' → показываем данные
 *  - 'failed' → показываем ошибку и кнопку «повторить»
 */
class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'yandex_url' => $this->yandex_url,
            'name' => $this->name,
            'address' => $this->address,
            'average_rating' => $this->average_rating,
            'rating_count' => $this->rating_count,
            'review_count' => $this->review_count,
            'parse_status' => $this->parse_status,
            'parse_error' => $this->parse_error,
            'parsed_at' => $this->parsed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
