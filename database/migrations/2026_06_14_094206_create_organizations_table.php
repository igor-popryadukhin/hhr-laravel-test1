<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Таблица организаций — одна запись = одна карточка Яндекс.Карт.
     *
     * parse_status — enum чтобы фронт мог понимать что происходит:
     *  pending   → только что добавили, парсер ещё не трогал
     *  parsing   → парсер работает (фронт показывает спиннер и поллит)
     *  completed → всё ок, данные загружены
     *  failed    → ошибка, текст в parse_error, фронт показывает кнопку «повторить»
     *
     * Одна организация на пользователя (updateOrCreate по user_id),
     * но связь hasMany на случай если потом расширим.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('yandex_url', 2048);
            $table->string('yandex_org_id', 255)->nullable()->comment('Числовой ID организации в Яндексе');
            $table->string('name', 512)->nullable();
            $table->string('address', 1024)->nullable();
            $table->decimal('average_rating', 3, 1)->nullable()->comment('Средний рейтинг, например 4.4');
            $table->unsignedInteger('rating_count')->nullable()->comment('Количество оценок (не отзывов!)');
            $table->unsignedInteger('review_count')->nullable()->comment('Количество текстовых отзывов');
            $table->enum('parse_status', ['pending', 'parsing', 'completed', 'failed'])->default('pending');
            $table->text('parse_error')->nullable()->comment('Текст ошибки парсинга для пользователя');
            $table->timestamp('parsed_at')->nullable()->comment('Когда последний раз успешно парсили');
            $table->timestamps();

            $table->index('user_id');
            $table->index('parse_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
