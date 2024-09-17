<?php

namespace App\Http\Controllers;


use App\Helpers\TrainingStatus;
use App\Models\Lesson;
use App\Models\Position;
use App\Models\PilotTraining;
use App\Models\PilotTrainingReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PilotTrainingReportController extends Controller
{
    public function create(PilotTraining $training)
    {
        $this->authorize('create', [PilotTrainingReport::class, $training]);

        if ($training->status < TrainingStatus::PRE_TRAINING->value) {
            return redirect(null, 400)->back()->withErrors('Training report cannot be created for a training not in progress.');
        }

        // Only get lessons where pilot_rating_id is $training->id
        $lessons = Lesson::where('pilot_rating_id', $training->pilotRatings[0]->id)->get();

        return view('pilot.training.report.create', compact('training', 'lessons'));
    }

    public function store(Request $request, PilotTraining $training)
    {
        $this->authorize('create', [PilotTrainingReport::class, $training]);

        $data = $this->validateRequest();
        
        $data['written_by_id'] = Auth::id();
        $data['pilot_training_id'] = $training->id;

        if (isset($data['report_date'])) {
            $data['report_date'] = Carbon::createFromFormat('d/m/Y', $data['report_date'])->format('Y-m-d H:i:s');
        }

        (isset($data['draft'])) ? $data['draft'] = true : $data['draft'] = false;

        // Remove attachments , they are added in next step
        unset($data['files']);
        $report = PilotTrainingReport::create($data);

        PilotTrainingObjectAttachmentController::saveAttachments($request, $report);

        return redirect(route('pilot.training.show', $training->id))->withSuccess('Report successfully created');

    }

    protected function validateRequest()
    {
        return request()->validate([
            'content' => 'sometimes|required',
            'contentimprove' => 'nullable',
            'report_date' => 'required|date_format:d/m/Y',
            'lesson_id' => 'required|exists:lessons,id',
            'draft' => 'sometimes',
            'files.*' => 'sometimes|file|mimes:pdf,xls,xlsx,doc,docx,txt,png,jpg,jpeg',
            'contentimprove' => 'sometimes|nullable|string',
        ]);
    }
}
