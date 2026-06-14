<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Авторизация через Sanctum-токены.
 *
 * SPA логинится, получает Bearer-токен, хранит в localStorage
 * и шлёт с каждым запросом. При логауте токен удаляется.
 * Сессий нет — все запросы чистый REST с токеном в заголовке.
 */
class AuthController extends Controller
{
    /**
     * Вход: почта + пароль → fresh Bearer-токен.
     * Токен один на сессию — при новом логине старый не трогаем,
     * просто создаём новый.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        $user = $request->user();
        // Ревокаем старые токены этого пользователя — один сеанс = один токен
        $user->tokens()->delete();
        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Выход: грохаем текущий токен.
     * Только тот, с которым пришёл запрос — остальные сессии не трогаем.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'OK']);
    }

    /**
     * Проверка «жив ли токен». SPA-роутер дёргает перед каждым переходом.
     * Если токен протух — 401, фронт сбрасывает localStorage и редиректит на /login.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
