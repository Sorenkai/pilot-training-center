<?php

namespace App\Http\Controllers;

use App\Models\PilotTrainingObject;
use App\Models\PilotTrainingObjectAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PilotTrainingObjectAttachmentController extends Controller
{
    public function store(Request $request, PilotTrainingObject $trainingObject)
    {
        $this->authorize('create', PilotTrainingObjectAttachment::class);

        $data = $request->validate([
            'file' => 'required|file|mimes:pdf,xls,xlsx,doc,docx,txt,png,jpg,jpeg',
            'hidden' => 'nullable',
        ]);

        $attachment_ids = self::saveAttachments($request, $trainingObject);

        if ($request->expectsJson()) {
            return json_encode([
                'id' => $attachment_ids,
                'message' => 'File(s) successfully uploaded',
            ]);
        }

        return redirect()->back()->withSuccess('Attachment successfully addded');
    }

    public function show(PilotTrainingObjectAttachment $attachment)
    {
        $this->authorize('view', $attachment);

        return redirect(route('file.get', ['file' => $attachment->file]));
    }

    public function destroy(Request $request, PilotTrainingObjectAttachment $attachment)
    {
        $this->authorize('delete', $attachment);

        Storage::delete($attachment->file->full_path);
        $attachment->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Attachment successfully deleted']);
        }

        return redirect()->back()->withSuccess('Attachment successfully deleted');
    }

    public static function saveAttachments(Request $request, PilotTrainingObject $object)
    {
        foreach ($request->files as $file) {
            if (! is_iterable($file)) {
                $file_id = FileController::saveFile($file);

                $object->attachments()->create([
                    'file_id' => $file_id,
                    'hidden' => false, // We hardcode this to false for now
                ]);
            } else {
                foreach ($file as $file2) {
                    $file_id = FileController::saveFile($file2);

                    $object->attachments()->create([
                        'file_id' => $file_id,
                        'hidden' => false, // We hardcode this to false for now
                    ]);
                }
            }
        }

        return $object->fresh()->attachments()->pluck('id')->toArray();
    }
}
