<?php

namespace App\Services\Contracts;

/**
 * Интерфейс парсера Яндекс.Карт.
 *
 * Нужен чтобы реализацию можно было подменить не трогая остальной код.
 * Сейчас реализация одна — Puppeteer (YandexMapsParserService).
 * Если завтра Яндекс закроет доступ браузерам — пишем PantherYandexMapsParserService
 * и меняем биндинг в AppServiceProvider, всё остальное даже не заметит.
 */
interface YandexMapsParserInterface
{
    /**
     * @return array{name: string, address: string|null, average_rating: float, rating_count: int, review_count: int, reviews: array}
     *
     * @throws \App\Exceptions\ParseException
     */
    public function parse(string $yandexUrl): array;
}
