<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Experience;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    // Crear reserva dinámica
    public function store(Request $request)
    {
        $request->validate([
            'experiencia_id' => 'required|string',
            'fecha' => 'required|string',
            'horario' => 'required|string',
            'personas' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        // Buscar experiencia real
        $experience = Experience::find($request->experiencia_id);

        if (!$experience) {
            return response()->json([
                'error' => 'La experiencia solicitada no existe en MongoDB.'
            ], 404);
        }

        $total = ($experience->precio ?? 0) * $request->personas;

        // Crear la reserva
        $booking = Booking::create([
            'turista_id' => $user->_id,
            'experiencia_id' => $experience->_id,
            'titulo_experiencia' => $experience->titulo ?? 'Tour en Oaxaca',
            'total_pago' => $total,
            'fecha' => $request->fecha,
            'horario' => $request->horario,
            'personas' => $request->personas,
            'estatus' => 'confirmada'
        ]);

        return response()->json([
            'mensaje' => '¡Reserva realizada con éxito!',
            'detalles_de_la_reserva' => $booking
        ], 201);
    }

    // Obtener las reservas del turista logueado
    public function myBookings(Request $request)
    {
        $bookings = Booking::where('turista_id', Auth::id())->orderBy('created_at', 'desc')->get();
        
        // Agregar información de la experiencia a cada reserva
        foreach ($bookings as $booking) {
            $booking->experiencia = Experience::find($booking->experiencia_id);
        }

        return response()->json($bookings, 200);
    }

    // Obtener reservas recibidas (para prestadores)
    public function receivedBookings(Request $request)
    {
        // Buscar todas las experiencias creadas por el prestador
        $myExperiencesIds = Experience::where('prestador_id', Auth::id())->pluck('_id')->toArray();

        // Buscar reservas correspondientes a estas experiencias
        $bookings = Booking::whereIn('experiencia_id', $myExperiencesIds)->orderBy('created_at', 'desc')->get();

        return response()->json($bookings, 200);
    }
}
