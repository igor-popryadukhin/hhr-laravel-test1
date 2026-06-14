<?php

namespace App\Providers;

use App\Services\Contracts\YandexMapsParserInterface;
use App\Services\YandexMapsParserService;
use Illuminate\Support\ServiceProvider;

/**
 * Точка входа для DI-биндингов.
 *
 * Сейчас здесь только биндинг интерфейса парсера на Pupeeteer-реализацию.
 * Если нужно будет подменить парсер (например на HTTP или Panther) —
 * меняем класс здесь, и весь код продолжает работать через интерфейс.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            YandexMapsParserInterface::class,
            YandexMapsParserService::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
