<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PilotTrainingObject extends Model
{
    public function attachments()
    {
        return $this->morphMany(PilotTrainingObjectAttachment::class, 'object');
    }

    public function pilotTraining()
    {
        return $this->belongsTo(PilotTraining::class);
    }
}
