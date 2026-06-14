<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Пагинированные отзывы — 50 штук на страницу.
 *
 * Отзывы уже лежат в БД (спарсили при сохранении ссылки),
 * поэтому пагинация работает мгновенно, без запросов к Яндексу.
 */
class ReviewController extends Controller
{
    /**
     * Список отзывов с пагинацией.
     *
     * Сортировка: сначала свежие по дате, потом по id
     * (на случай если несколько отзывов в один день).
     */
    public function index(int $organizationId, Request $request): JsonResponse
    {
        $organization = Organization::where('user_id', auth()->id())->findOrFail($organizationId);

        $reviews = $organization->reviews()
            ->orderBy('review_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50);

        return response()->json([
            'data' => ReviewResource::collection($reviews->items()),
            'current_page' => $reviews->currentPage(),
            'last_page' => $reviews->lastPage(),
            'per_page' => $reviews->perPage(),
            'total' => $reviews->total(),
        ]);
    }
}
