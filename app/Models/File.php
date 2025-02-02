<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $guarded = [];

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    /**
     * Gets the user that uploaded the file
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the training report attachment to which the file is used
     *
     * @return |null
     */
    public function getTrainingReportAttachmentAttribute()
    {
        return count(PilotTrainingObjectAttachment::where('file_id', $this->id)->get()) != 0 ? PilotTrainingObjectAttachment::where('file_id', $this->id)->first() : null;
    }

    public function getExamAttachmentAttribute()
    {
        return count(ExamObjectAttachment::where('file_id', $this->id)->get()) != 0 ? ExamObjectAttachment::where('file_id', $this->id)->first() : null;
    }

    /**
     * Get the full server path.
     * Can simply be called as $file->full_path
     *
     * @return string
     */
    public function getFullPathAttribute()
    {
        return 'public/files/' . $this->path;
    }
}
