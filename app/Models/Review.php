<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model; 

class Review extends Model
{
    protected $collection = 'reviews';
    protected $keyType = 'string';

    protected $fillable = [
        'experience_id',
        'user_id',
        'rating',      // Calificación del 1 al 5
        'comment',     // El texto de la reseña
        'respuesta_prestador'
    ];

    // Relación: Una reseña pertenece a un usuario (turista)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación: Una reseña pertenece a una experiencia
    public function experience()
    {
        return $this->belongsTo(Experience::class);
    }
}
