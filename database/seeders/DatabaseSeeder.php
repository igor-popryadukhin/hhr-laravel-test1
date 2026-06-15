<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Один тестовый пользователь — без регистрации, только логин.
 *
 * Учётка: test@example.com / password
 * Используется для демонстрации и локальной разработки.
 * В production такой же подход: сидим одного пользователя
 * и от него работаем (внутренний инструмент, не публичный сервис).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ],
        );
    }
}
