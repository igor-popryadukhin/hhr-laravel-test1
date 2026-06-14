# Парсер отзывов Яндекс.Карт

Приложение для сбора отзывов организаций с Яндекс.Карт. Laravel (бэкенд/API) + Vue 3 (SPA).

## Функциональность

- Авторизация (логин/пароль) через Laravel Sanctum
- Сохранение ссылки на карточку организации Яндекс.Карт
- Автоматический парсинг всех отзывов, рейтинга и статистики
- Постраничный просмотр отзывов (50 на страницу, без перезагрузки)
- Повторный парсинг по требованию

## Быстрый старт (Docker)

```bash
git clone <repo-url> && cd hhr-laravel-test1
cp .env.example .env
docker compose up -d
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --seed --force
```

Приложение будет доступно на `http://localhost:8000`.

Учётные данные для входа: `test@example.com` / `password`.

## Локальная разработка

### Требования

- PHP 8.4+
- Composer 2
- Node.js 24+
- npm 11+
- SQLite (для разработки) или MySQL

### Установка

```bash
git clone <repo-url> && cd hhr-laravel-test1
composer install
cp .env.example .env
php artisan key:generate
```

Настройте `.env`:
```
DB_CONNECTION=sqlite  # для разработки
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
```

```bash
php artisan migrate --seed
npm install
```

### Запуск

Два терминала:

```bash
# Терминал 1: Laravel API
php artisan serve

# Терминал 2: Vite dev-сервер
npm run dev
```

Откройте `http://localhost:5173`.

## Переменные окружения

| Переменная | Описание | По умолчанию |
|---|---|---|
| `SANCTUM_STATEFUL_DOMAINS` | Домен SPA для Sanctum cookie | `localhost:5173` |
| `SESSION_DOMAIN` | Домен сессионной куки | `localhost` |
| `YANDEX_REQUEST_DELAY_MS` | Задержка между запросами к Яндексу (мс) | `300` |
| `YANDEX_MAX_REVIEWS` | Максимум собираемых отзывов | `1000` |
| `YANDEX_TIMEOUT` | Таймаут HTTP-запросов (сек) | `30` |

## Подход к парсингу

### Выбранное решение: Headless-браузер (Puppeteer)

Парсер использует **Puppeteer** (headless Chrome) — это единственный надёжный способ обойти защиту Яндекса от ботов и получить все отзывы, подгружаемые скриптом при прокрутке.

Архитектура:
1. **PHP-сервис** (`app/Services/YandexMapsParserService.php`) вызывает Node.js скрипт через `Illuminate\Support\Facades\Process`
2. **Node.js скрипт** (`resources/js/parser/yandex-parser.js`) управляет браузером через Puppeteer
3. Скрипт возвращает JSON, PHP-сервис его разбирает и сохраняет в БД

Алгоритм работы скрипта:
1. **Запуск браузера** — headless Chromium с флагами `--disable-blink-features=AutomationControlled`, `--no-sandbox`, скрытие `navigator.webdriver`
2. **Навигация** на `/reviews/` организации с реалистичным `User-Agent`
3. **Прокрутка** для загрузки всех отзывов (до 60 итераций, задержки 300–1000 мс между скроллами, остановка при отсутствии нового контента)
4. **Извлечение данных** через целевые DOM-селекторы:
   - `h1` — название организации
   - `[aria-label*="Оценка"]` — средний рейтинг
   - `.business-rating-amount-view._summary` — количество оценок
   - `meta[name="description"]` — количество отзывов
   - `.business-reviews-card-view__review` — карточки отзывов
   - `.business-review-view__info` — автор + дата
   - `.business-review-view__body` — текст отзыва

### Обход защиты от ботов

- **Headless Chrome с антидетектом**: `--disable-blink-features=AutomationControlled`, `navigator.webdriver = false`
- **Реалистичные задержки** между скроллами (300–1000 мс, случайный разброс)
- Полноценный рендеринг JavaScript — Яндекс не отличает от реального пользователя
- При ошибке (капча, таймаут, изменение разметки) — `ParseException` с сообщением пользователю

### Почему именно Puppeteer, а не HTTP-клиент?

Попытка использования HTTP-клиента (Laravel HTTP Client) для разбора внутреннего API Яндекса провалилась: Яндекс определяет серверные запросы и показывает капчу. Headless-браузер:
- Исполняет JavaScript, загружающий отзывы при прокрутке
- Имеет те же fingerprint-характеристики, что и обычный Chrome
- Позволяет извлечь данные из DOM после полного рендеринга

Парсер изолирован за интерфейсом `YandexMapsParserInterface` — реализацию можно заменить без изменения остального кода.

### Хранение данных

Данные парсятся полностью при сохранении ссылки и кэшируются в БД (таблицы `organizations` и `reviews`). Это сделано осознанно:
- ~600 отзывов — управляемый объём для БД (десятки-сотни килобайт)
- Пагинация работает мгновенно через SQL `paginate()`, без запросов к Яндексу
- Повторный парсинг (`POST /api/organizations/{id}/reparse`) обновляет данные через `upsert()`, не создавая дубликатов

## Структура проекта

### Бэкенд

```
app/
  Http/
    Controllers/Api/
      AuthController.php        # Вход, выход, текущий пользователь
      SettingsController.php    # Настройки (сохранение URL)
      OrganizationController.php # Данные организации
      ReviewController.php      # Пагинированные отзывы
    Requests/
      UpdateSettingsRequest.php  # Валидация URL
    Resources/
      OrganizationResource.php   # JSON:API для организации
      ReviewResource.php         # JSON:API для отзыва
  Models/
    User.php                     # Пользователь (Sanctum)
    Organization.php             # Организация
    Review.php                   # Отзыв
  Services/
    Contracts/
      YandexMapsParserInterface.php  # Интерфейс парсера
    YandexMapsParserService.php      # Основной парсер (HTTP)
    OrganizationService.php          # Оркестрация парсинга
  Exceptions/
    ParseException.php           # Ошибка парсинга
config/
  yandex.php                    # Настройки парсера
```

### Фронтенд

```
resources/js/
  app.js                        # Точка входа Vue
  App.vue                       # Корневой компонент
  composables/
    useAuth.js                  # Авторизация
    useSettings.js              # Настройки (URL)
    useOrganization.js          # Данные организации
    useReviews.js               # Отзывы + пагинация
  pages/
    LoginPage.vue               # Форма входа
    SettingsPage.vue            # Настройки (ссылка на Яндекс.Карты)
    OrganizationPage.vue        # Просмотр данных организации
  components/
    AuthenticatedLayout.vue     # Обёртка для авторизованных страниц
    NavBar.vue                  # Навигация
    OrganizationHeader.vue      # Статистика организации
    ReviewList.vue              # Список отзывов
    ReviewCard.vue              # Карточка отзыва
    PaginationBar.vue           # Пагинация
    SpinnerOverlay.vue          # Индикатор загрузки
```

## API

| Метод | Путь | Auth | Описание |
|---|---|---|---|
| POST | `/api/login` | — | Вход (email, password) |
| POST | `/api/logout` | Sanctum | Выход |
| GET | `/api/user` | Sanctum | Текущий пользователь |
| GET | `/api/settings` | Sanctum | Настройки + статус парсинга |
| PUT | `/api/settings` | Sanctum | Сохранить URL, запустить парсинг |
| GET | `/api/organizations/{id}` | Sanctum | Данные организации |
| GET | `/api/organizations/{id}/reviews?page=` | Sanctum | Пагинированные отзывы (50/стр) |
| POST | `/api/organizations/{id}/reparse` | Sanctum | Повторный парсинг |

## Что можно улучшить

При наличии большего времени я бы сделал:

1. **Асинхронный парсинг через очереди** — сейчас парсинг выполняется синхронно в HTTP-запросе (ждать до 3 минут). Стоит вынести в Laravel Queue: сразу возвращать `parse_status: 'queued'`, а фронтенд пусть опрашивает статус.

2. **Резидентные прокси** — при парсинге с серверного IP Яндекс со временем может начать блокировать запросы. Ротация резидентных прокси решит проблему масштабирования.

3. **Полноценный мониторинг** — Sentry/Bugsnag для отслеживания ошибок парсинга, алерты при изменении DOM-структуры Яндекса.

4. **Тесты** — unit-тесты для PHP-сервисов, интеграционные тесты для API, e2e для SPA.

5. **Несколько организаций** — сейчас одна организация на пользователя. Расширить до списка с переключением.

6. **Инкрементальное обновление** — при reparse не пересобирать все отзывы заново, а только подгружать новые (по `yandex_review_id`).
