<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;

/**
 * Настройки приложения — по сути одна настройка: ссылка на Яндекс.Карты.
 *
 * Одна организация на пользователя (по условию задачи).
 * При сохранении ссылки сразу запускается парсинг.
 * Фронт потом polls статус пока не завершится.
 */
class SettingsController extends Controller
{
    /**
     * Текущая организация пользователя (или null если ещё не добавлял).
     */
    public function show(): JsonResponse
    {
        $organization = Organization::where('user_id', auth()->id())->latest()->first();

        return response()->json([
            'organization' => $organization ? new OrganizationResource($organization) : null,
        ]);
    }

    /**
     * Сохраняет ссылку и дёргает парсер.
     * Валидация ссылки — в UpdateSettingsRequest.
     * Парсинг синхронный (до 3 минут), OrganizationService сам обработает ошибки.
     */
    public function update(UpdateSettingsRequest $request, OrganizationService $orgService): JsonResponse
    {
        $organization = $orgService->saveAndParse(auth()->id(), $request->yandex_url);

        return response()->json([
            'organization' => new OrganizationResource($organization),
        ]);
    }
}
