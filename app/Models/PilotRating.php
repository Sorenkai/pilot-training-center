<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PilotRating extends Model
{
    protected $table = 'pilot_ratings';

    public function pilotTrainings()
    {
        return $this->belongsToMany(PilotTraining::class);
    }
}
