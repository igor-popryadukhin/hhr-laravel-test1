<?php

/**
 * Настройки парсинга Яндекс.Карт.
 *
 * Часть опций (request_delay_ms, max_pages) сейчас не используются
 * в Puppeteer-парсере, но оставлены на случай если будем комбинировать
 * HTTP + браузер.
 *
 * org_url — главный регекс для извлечения числового ID организации.
 * Менять здесь если Яндекс сменит структуру URL.
 */
return [
    'request_delay_ms' => (int) env('YANDEX_REQUEST_DELAY_MS', 300),
    'max_reviews' => (int) env('YANDEX_MAX_REVIEWS', 1000),
    'max_pages' => (int) env('YANDEX_MAX_PAGES', 30),
    'timeout' => (int) env('YANDEX_TIMEOUT', 30),
    'retries' => (int) env('YANDEX_RETRIES', 2),

    'user_agents' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
    ],

    'patterns' => [
        // Выдирает числовой ID из URL карточки организации
        'org_url' => '~^https?://yandex\.(?:ru|com|by|kz|ua)/maps/org/[^/]+/(\d+)/?~',
        // Остальные паттерны — запасные для HTTP-парсинга (если вдруг понадобится)
        'json_ld' => '/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s',
        'initial_state' => '/window\.__INITIAL_STATE__\s*=\s*({.*?});\s*<\/script>/s',
        'serialized_state' => '/<script[^>]+id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s',
    ],
];
