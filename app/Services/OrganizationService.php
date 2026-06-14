<?php

namespace App\Services;

use App\Exceptions\ParseException;
use App\Models\Organization;
use App\Models\Review;
use App\Services\Contracts\YandexMapsParserInterface;
use Illuminate\Support\Facades\DB;

/**
 * Оркеструет весь флоу: сохранил ссылку → запустил парсер → разложил отзывы по БД.
 *
 * Все операции внутри транзакции. Если парсер упал — организация остаётся в БД
 * со статусом failed и сообщением об ошибке, пользователь видит кнопку «повторить».
 *
 * upsert по yandex_review_id защищает от дублей при повторном парсинге.
 */
class OrganizationService
{
    public function __construct(
        private YandexMapsParserInterface $parser,
    ) {}

    /**
     * Первый запуск: создаёт (или обновляет) организацию и сразу парсит.
     * Одна организация на пользователя — updateOrCreate по user_id.
     */
    public function saveAndParse(int $userId, string $yandexUrl): Organization
    {
        $orgId = $this->extractOrgIdFromUrl($yandexUrl);

        return DB::transaction(function () use ($userId, $yandexUrl, $orgId) {
            $organization = Organization::updateOrCreate(
                ['user_id' => $userId],
                [
                    'yandex_url' => $yandexUrl,
                    'yandex_org_id' => $orgId,
                    'parse_status' => 'parsing',
                ]
            );

            $this->runParse($organization, $yandexUrl);

            return $organization->fresh();
        });
    }

    /**
     * Повторный парсинг существующей организации.
     * Старые отзывы не удаляются — upsert обновит совпавшие и добавит новые.
     */
    public function reparse(Organization $organization): Organization
    {
        return DB::transaction(function () use ($organization) {
            $organization->update(['parse_status' => 'parsing', 'parse_error' => null]);
            $this->runParse($organization, $organization->yandex_url);

            return $organization->fresh();
        });
    }

    /**
     * Общая логика: вызвали парсер → разложили данные.
     * Вынесено чтобы не копипастить между saveAndParse и reparse.
     */
    private function runParse(Organization $organization, string $yandexUrl): void
    {
        try {
            $data = $this->parser->parse($yandexUrl);

            // Сначала пишем отзывы, потом обновляем организацию.
            // Если upsert упадёт — org останется в parsing, а не в битом completed.
            $this->upsertReviews($organization->id, $data['reviews']);

            $organization->update([
                'name' => $data['name'],
                'address' => $data['address'],
                'average_rating' => $data['average_rating'],
                'rating_count' => $data['rating_count'],
                'review_count' => count($data['reviews']),
                'parse_status' => 'completed',
                'parsed_at' => now(),
                'parse_error' => null,
            ]);

        } catch (ParseException $e) {
            $organization->update([
                'parse_status' => 'failed',
                'parse_error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            $organization->update([
                'parse_status' => 'failed',
                'parse_error' => 'Внутренняя ошибка парсера. Попробуйте позже.',
            ]);
        }
    }

    /**
     * Пишем отзывы пачками по 100 штук — чтобы не упереться в лимит плейсхолдеров БД.
     * upsert по yandex_review_id: что новое — добавится, что старое — обновится.
     */
    private function upsertReviews(int $organizationId, array $reviews): void
    {
        foreach (array_chunk($reviews, 100) as $chunk) {
            $rows = array_map(fn($r) => [
                'organization_id' => $organizationId,
                'yandex_review_id' => $r['yandex_review_id'],
                'author_name' => $r['author_name'],
                'author_avatar' => $r['author_avatar'] ?? null,
                'rating' => $r['rating'],
                'text' => $r['text'] ?? '',
                'review_date' => $this->normalizeDate($r['review_date']),
                'created_at' => now(),
                'updated_at' => now(),
            ], $chunk);

            Review::upsert(
                $rows,
                ['organization_id', 'yandex_review_id'],
                ['author_name', 'author_avatar', 'rating', 'text', 'review_date', 'updated_at']
            );
        }
    }

    /**
     * Даты от парсера могут быть в русском формате («7 августа 2023»),
     * в ISO (Y-m-d), или вообще отсутствовать. Приводим к единому виду.
     */
    private function normalizeDate(mixed $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        if (is_string($date)) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $date;
            }
            if (is_numeric($date) && strlen($date) > 10) {
                $date = (int) $date / 1000;
            }
            return date('Y-m-d', is_numeric($date) ? (int) $date : strtotime($date));
        }

        return date('Y-m-d', (int) $date);
    }

    /**
     * Из URL вида .../org/slug/123456789/ выдираем числовой ID.
     * Не получилось — значит ссылка кривая, кидаем исключение.
     */
    private function extractOrgIdFromUrl(string $url): string
    {
        $pattern = config('yandex.patterns.org_url');
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        throw new ParseException('Не удалось извлечь ID организации из ссылки.');
    }
}
