<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация ссылки на Яндекс.Карты.
 *
 * Принимаем только URL вида:
 * https://yandex.ru/maps/org/название/123456789/
 *
 * Регекс жёсткий: https://, домен yandex.{ru,com,by,kz,ua},
 * путь /maps/org/{slug}/{numeric_id}/.
 * Это отсекает левые ссылки на главную, поиск, маршруты и т.д.
 */
class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'yandex_url' => [
                'required',
                'url',
                function ($attribute, $value, $fail) {
                    if (!preg_match('~^https://yandex\.(?:ru|com|by|kz|ua)/maps/org/[^/]+/\d+/?(?:\?.*)?(?:#.*)?$~', $value)) {
                        $fail('Ссылка должна вести на карточку организации в Яндекс.Картах (yandex.ru/maps/org/название/123456789/).');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'yandex_url.required' => 'Укажите ссылку на организацию в Яндекс.Картах.',
            'yandex_url.url' => 'Некорректный формат ссылки.',
        ];
    }
}
