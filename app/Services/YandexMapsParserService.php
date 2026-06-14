<?php

namespace App\Services;

use App\Exceptions\ParseException;
use App\Services\Contracts\YandexMapsParserInterface;
use Illuminate\Support\Facades\Process;

/**
 * Парсер на Puppeteer — дёргает Node.js скрипт, который открывает
 * headless Chrome и вытягивает все отзывы через DOM, а не через API.
 *
 * Почему не HTTP-клиент: Яндекс палит серверные запросы и кидает капчу.
 * Браузер с антидетектом притворяется живым пользователем — работает стабильно.
 *
 * Если Яндекс опять поменяет вёрстку, править нужно только JS-скрипт
 * в resources/js/parser/yandex-parser.js, сюда лезть не придётся.
 */
class YandexMapsParserService implements YandexMapsParserInterface
{
    /**
     * Дёргает Node.js и возвращает структурированный массив.
     * Таймаут 3 минуты — хватает на ~60 скроллов и загрузку ~600 отзывов.
     */
    public function parse(string $yandexUrl): array
    {
        $scriptPath = base_path('resources/js/parser/yandex-parser.js');

        if (!file_exists($scriptPath)) {
            throw new ParseException('Скрипт парсера не найден.');
        }

        $command = ['node', $scriptPath, $yandexUrl];

        $result = Process::timeout(180)
            ->run($command);

        if (!$result->successful()) {
            $errorOutput = $result->errorOutput();
            $errorData = json_decode($errorOutput, true);
            $message = $errorData['error'] ?? $errorOutput;

            throw new ParseException(
                'Ошибка парсинга: ' . (is_string($message) ? $message : 'Неизвестная ошибка'),
            );
        }

        $data = json_decode($result->output(), true);

        if (!$data || isset($data['error'])) {
            throw new ParseException(
                'Ошибка парсинга: ' . ($data['error'] ?? 'Не удалось разобрать ответ'),
            );
        }

        if (empty($data['name'])) {
            throw new ParseException('Не удалось определить название организации.');
        }

        return [
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'average_rating' => (float) ($data['average_rating'] ?? 0),
            'rating_count' => (int) ($data['rating_count'] ?? 0),
            'review_count' => (int) ($data['review_count'] ?? 0),
            'reviews' => array_map(fn($r) => [
                'yandex_review_id' => (string) ($r['yandex_review_id'] ?? uniqid('rev-')),
                'author_name' => $r['author_name'] ?? 'Аноним',
                'author_avatar' => $r['author_avatar'] ?? null,
                'rating' => min(5, max(0, (int) ($r['rating'] ?? 0))),
                'text' => $r['text'] ?? '',
                'review_date' => $this->normalizeDate($r['review_date'] ?? null),
            ], $data['reviews'] ?? []),
        ];
    }

    /**
     * Даты от Яндекса приходят в русском формате: «7 августа 2023».
     * Приводим к Y-m-d, заодно обрабатываем краевые случаи.
     */
    private function normalizeDate(mixed $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        $months = [
            'января' => '01', 'февраля' => '02', 'марта' => '03', 'апреля' => '04',
            'мая' => '05', 'июня' => '06', 'июля' => '07', 'августа' => '08',
            'сентября' => '09', 'октября' => '10', 'ноября' => '11', 'декабря' => '12',
        ];

        foreach ($months as $ru => $num) {
            if (preg_match("/(\d{1,2})\s+{$ru}\s+(\d{4})/u", $date, $m)) {
                return "{$m[2]}-{$num}-" . str_pad($m[1], 2, '0', STR_PAD_LEFT);
            }
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        $ts = strtotime($date);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}
