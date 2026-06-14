<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;

/**
 * Данные организации: метаинфа + кнопка «перепарсить».
 *
 * Всегда проверяем что организация принадлежит текущему юзеру
 * (where user_id) — чужие orgs не светим.
 */
class OrganizationController extends Controller
{
    /**
     * Карточка организации: название, адрес, рейтинг, счётчики, статус парсинга.
     */
    public function show(int $id): JsonResponse
    {
        $organization = Organization::where('user_id', auth()->id())->findOrFail($id);

        return response()->json([
            'organization' => new OrganizationResource($organization),
        ]);
    }

    /**
     * Ручной запуск повторного парсинга.
     * Старые отзывы не теряются — upsert обновит / добавит.
     */
    public function reparse(int $id, OrganizationService $orgService): JsonResponse
    {
        $organization = Organization::where('user_id', auth()->id())->findOrFail($id);

        $organization = $orgService->reparse($organization);

        return response()->json([
            'organization' => new OrganizationResource($organization),
        ]);
    }
}
