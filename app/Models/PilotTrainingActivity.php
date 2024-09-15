<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PilotTrainingActivity extends Model
{
    use HasFactory;

    public $table = 'pilot_training_activity';

    public $fillable = [
        'triggered_by_id', 'type', 'old_data', 'new_data', 'comment',
    ];

    public function pilotTraining()
    {
        return $this->belongsTo(PilotTraining::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'triggered_by_id');
    }
}
