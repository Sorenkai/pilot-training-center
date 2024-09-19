<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstructorEndorsement extends Model
{
    use HasFactory;

    protected $table = 'instructor_endorsements';

    public function pilotRatings()
    {
        return $this->belongsToMany(PilotRating::class, 'pilot_rating_id');
    }

    public function user()
    {
        return $this->belongsToMany(User::class);
    }
}
