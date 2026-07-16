<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Booking extends Model
{
    // Nombre de la colección en MongoDB
    protected $collection = 'bookings';

    protected $fillable = [
        'turista_id',
        'experiencia_id',
        'titulo_experiencia',
        'total_pago',
        'fecha',
        'horario',
        'personas',
        'estatus'
    ];
}
