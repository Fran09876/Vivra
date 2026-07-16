<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Experience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // INVITADOS Y TODOS: Ver las reseñas de una experiencia específica
    public function index($experience_id)
    {
        $reviews = Review::where('experience_id', $experience_id)->with('user')->get();
        return response()->json($reviews, 200);
    }

    // TURISTA: Crear una reseña
    public function store(Request $request, $experience_id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string'
        ]);

        // Verificamos que la experiencia exista
        if (!Experience::find($experience_id)) {
            return response()->json(['error' => 'Experiencia no encontrada'], 404);
        }

        $review = Review::create([
            'experience_id' => $experience_id,
            'user_id' => Auth::id(), // El ID lo tomamos directo del token por seguridad
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json(['mensaje' => 'Reseña creada con éxito', 'data' => $review], 201);
    }

    // TURISTA: Editar su propia reseña
    public function update(Request $request, $id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json(['error' => 'Reseña no encontrada'], 404);
        }

        // ¡Seguridad extra! Verificamos que el turista sea el dueño de la reseña
        if ($review->user_id !== Auth::id()) {
            return response()->json(['error' => 'Solo puedes editar tus propias reseñas'], 403);
        }

        $review->update($request->only(['rating', 'comment']));

        return response()->json(['mensaje' => 'Reseña actualizada', 'data' => $review], 200);
    }

    public function destroy($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json(['error' => 'Reseña no encontrada'], 404);
        }

        // 🔐 REGLA ESTRICTA: Si el usuario es Turista, obligatoriamente debe ser el dueño
        if (Auth::user()->role === 'turista' && $review->user_id !== Auth::id()) {
            return response()->json(['error' => 'Acceso denegado. Solo puedes borrar tus propias reseñas.'], 403);
        }

        // Si es Admin, el condicional se salta y se borra la reseña directamente (Borrar Reseñas de todos)
        $review->delete();
        return response()->json(['mensaje' => 'Reseña eliminada correctamente'], 200);
    }

    // NUEVO: Prestador responde a una reseña
    public function reply(Request $request, $id)
    {
        $request->validate([
            'respuesta' => 'required|string|max:500'
        ]);

        $review = Review::find($id);
        if (!$review) {
            return response()->json(['error' => 'Reseña no encontrada'], 404);
        }

        // Validar que el usuario sea el prestador de la experiencia
        $experiencia = Experience::find($review->experience_id);
        if (!$experiencia) {
            return response()->json(['error' => 'Experiencia asociada no encontrada'], 404);
        }

        if ($experiencia->prestador_id !== Auth::id()) {
            return response()->json(['error' => 'No tienes permiso para responder a esta reseña. No eres el creador de esta experiencia.'], 403);
        }

        // Guardar la respuesta embebida (compatible con MongoDB)
        $review->respuesta_prestador = [
            'texto' => $request->respuesta,
            'prestador_id' => Auth::id(),
            'created_at' => now()->toIso8601String()
        ];
        $review->save();

        return response()->json(['mensaje' => 'Respuesta publicada correctamente', 'data' => $review], 200);
    }

    // NUEVO: Prestador elimina su respuesta
    public function destroyReply($id)
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json(['error' => 'Reseña no encontrada'], 404);
        }

        // Validar que el usuario sea el prestador de la experiencia
        $experiencia = Experience::find($review->experience_id);
        if (!$experiencia) {
            return response()->json(['error' => 'Experiencia asociada no encontrada'], 404);
        }

        if ($experiencia->prestador_id !== Auth::id()) {
            return response()->json(['error' => 'No tienes permiso para eliminar esta respuesta.'], 403);
        }

        // Eliminar campo en MongoDB
        $review->unset('respuesta_prestador');

        return response()->json(['mensaje' => 'Respuesta eliminada correctamente'], 200);
    }
}

