<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;

// =========================================================================
// RUTAS PÚBLICAS (Invitados y cualquier usuario sin logearse)
// =========================================================================
Route::post('/registro', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/experiencias', [ExperienceController::class, 'index']);
Route::get('/experiencias/{id}', [ExperienceController::class, 'show']);
Route::get('/experiencias/{experience_id}/resenas', [ReviewController::class, 'index']);


// =========================================================================
// RUTAS PROTEGIDAS (Requieren inicio de sesión y Token válido)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- EXCLUSIVO: PRESTADOR ---
    Route::middleware('role:prestador')->group(function () {
        Route::post('/experiencias', [ExperienceController::class, 'store']); // Crear Experiencias
        Route::get('/mis-experiencias', [ExperienceController::class, 'myExperiences']); // Ver las suyas
        Route::post('/resenas/{id}/respuesta', [ReviewController::class, 'reply']); // Responder a Reseña
        Route::delete('/resenas/{id}/respuesta', [ReviewController::class, 'destroyReply']); // Eliminar respuesta de reseña
        Route::get('/reservas-recibidas', [BookingController::class, 'receivedBookings']); // Ver reservas recibidas
    });

    // --- COMPARTIDO: ADMIN Y PRESTADOR ---
    Route::middleware('role:admin,prestador')->group(function () {
        Route::put('/experiencias/{id}', [ExperienceController::class, 'update']); // Editar Experiencias
        Route::delete('/experiencias/{id}', [ExperienceController::class, 'destroy']); // Borrar Experiencias
    });

    // --- EXCLUSIVO: TURISTA ---
    Route::middleware('role:turista')->group(function () {
        Route::post('/carrito-comprar', [BookingController::class, 'store']); // Comprar Experiencia
        Route::get('/mis-reservas', [BookingController::class, 'myBookings']); // Ver mis reservas
        Route::post('/experiencias/{experience_id}/resenas', [ReviewController::class, 'store']); // Crear Reseñas
        Route::put('/resenas/{id}', [ReviewController::class, 'update']); // Editar sus Reseñas
    });

    // --- COMPARTIDO: ADMIN Y TURISTA (Para evitar colisiones de URL) ---
    Route::middleware('role:admin,turista')->group(function () {
        Route::delete('/resenas/{id}', [ReviewController::class, 'destroy']); // Borrar Reseñas (Lógica interna)
    });

    // --- EXCLUSIVO: ADMIN ---
    Route::middleware('role:admin')->group(function () {
        Route::get('/usuarios', [AuthController::class, 'index']); // Ver Usuarios (todos)
        Route::get('/usuarios/{id}', [AuthController::class, 'show']); // Ver Usuario por ID
        Route::post('/usuarios', [AuthController::class, 'store']); // Crear Usuarios
        Route::put('/usuarios/{id}', [AuthController::class, 'update']); // Editar Usuarios (todos)
        Route::delete('/usuarios/{id}', [AuthController::class, 'destroy']); // Borrar Usuarios (todos)
    });

// Ruta de debug token
    Route::get('/debug-token', function (Illuminate\Http\Request $request) {
        $token = $request->bearerToken();
        if (!$token) return response()->json(['error' => 'No enviaste el Bearer Token en Postman']);

        $accessToken = \App\Models\PersonalAccessToken::findToken($token);
        if (!$accessToken) return response()->json(['error' => 'El token no existe en Mongo o el ID no coincide']);

        $isExpired = false;
        $expiration = config('sanctum.expiration');
        if ($expiration) {
            $isExpired = $accessToken->created_at->lte(now()->subMinutes($expiration));
        }

        return response()->json([
            '1_token_encontrado' => true,
            '2_fecha_creacion' => $accessToken->created_at,
            '3_fecha_actual_servidor' => now(),
            '4_esta_expirado' => $isExpired,
            '5_usuario_encontrado' => $accessToken->tokenable ? $accessToken->tokenable->name : 'NULO - Falla la relación'
        ]);
    });
});
