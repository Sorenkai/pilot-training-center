<?php

namespace App\Models;

use App\Models\Exam;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Model;

class PilotRating extends Model
{
    protected $table = 'pilot_ratings';

    public function pilotTrainings()
    {
        return $this->belongsToMany(PilotTraining::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
