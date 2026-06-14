<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Таблица отзывов — по одному на каждый отзыв с Яндекс.Карт.
     *
     * yandex_review_id уникальный — по нему делаем upsert при reparse:
     * существующие отзывы обновляются, новые добавляются, дублей нет.
     *
     * Индексы на review_date и rating — для сортировки и фильтрации в API.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('yandex_review_id', 128)->comment('Уникальный ID отзыва в Яндексе');
            $table->string('author_name', 512);
            $table->string('author_avatar', 2048)->nullable();
            $table->unsignedTinyInteger('rating')->comment('Оценка 1-5');
            $table->text('text')->nullable();
            $table->date('review_date')->nullable();
            $table->timestamps();

            // Составной unique — чтобы rev-N у разных организаций не конфликтовали
            $table->unique(['organization_id', 'yandex_review_id']);
            // Составной индекс для пагинации: WHERE org_id + ORDER BY review_date, id
            $table->index(['organization_id', 'review_date', 'id']);
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
