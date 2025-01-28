<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamObject extends Model
{
    public function attachments()
    {
        return $this->morphOne(ExamObjectAttachment::class, 'object');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
