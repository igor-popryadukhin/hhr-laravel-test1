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
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}
