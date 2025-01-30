<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exam extends ExamObject
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pilotRating()
    {
        return $this->belongsTo(PilotRating::class);
    }

    public function pilotTraining()
    {
        return $this->belongsTo(PilotTraining::class);
    }
}
