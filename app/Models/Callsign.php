<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Callsign extends Model
{
    protected $fillable = ['callsign', 'training_level', 'training_id', 'user_id'];

    public function pilotTraining()
    {
        return $this->hasOne(PilotTraining::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
