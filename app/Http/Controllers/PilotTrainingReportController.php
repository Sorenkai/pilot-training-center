<?php

namespace App\Http\Controllers;

use App\Helpers\TrainingStatus;
use App\Models\Lesson;
use App\Models\PilotTraining;
use App\Models\PilotTrainingReport;
use App\Notifications\PilotTrainingReportNotification;
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

        // Convert hours flown to decimal
        $time = $data['instructor_hours'];
        [$hours, $minutes] = explode(':', $time);
        $data['instructor_hours'] = $hours + ($minutes / 60);

        if (isset($data['report_date'])) {
            $data['report_date'] = Carbon::createFromFormat('d/m/Y', $data['report_date'])->format('Y-m-d H:i:s');
        }

        (isset($data['draft'])) ? $data['draft'] = true : $data['draft'] = false;

        // Remove attachments , they are added in next step
        unset($data['files']);
        $report = PilotTrainingReport::create($data);

        PilotTrainingObjectAttachmentController::saveAttachments($request, $report);

        if ($report->draft != true && $training->user->setting_notify_newreport) {
            $training->user->notify(new PilotTrainingReportNotification($training, $report));
        }

        return redirect(route('pilot.training.show', $training->id))->withSuccess('Report successfully created');
    }

    public function edit(PilotTrainingReport $report)
    {
        $this->authorize('update', $report);

        $lessons = Lesson::all();

        return view('pilot.training.report.edit', compact('report', 'lessons'));
    }

    public function update(Request $request, PilotTrainingReport $report)
    {
        $this->authorize('update', $report);
        $oldDraftStatus = $report->fresh()->draft;

        $data = $this->validateRequest();

        if (isset($data['report_date'])) {
            $data['report_date'] = Carbon::createFromFormat('d/m/Y', $data['report_date'])->format('Y-m-d H:i:s');
        }

        (isset($data['draft'])) ? $data['draft'] = true : $data['draft'] = false;

        // Convert hours flown to decimal
        $time = $data['instructor_hours'];
        [$hours, $minutes] = explode(':', $time);
        $data['instructor_hours'] = $hours + ($minutes / 60);

        $report->update($data);

        // Notify student of new training request if it's not a draft anymore
        if ($oldDraftStatus == true && $report->draft == false && $report->pilotTraining->user->setting_notify_newreport) {
            $report->training->user->notify(new PilotTrainingReportNotification($report->training, $report));
        }

        return redirect()->intended(route('pilot.training.show', $report->pilotTraining->id))->withSuccess('Training report successfully updated');
    }

    protected function validateRequest()
    {
        return request()->validate([
            'content' => 'sometimes|required',
            'contentimprove' => 'nullable',
            'instructor_hours' => 'required|date_format:H:i|not_in:00:00',
            'report_date' => 'required|date_format:d/m/Y',
            'lesson_id' => 'required|exists:lessons,id',
            'draft' => 'sometimes',
            'files.*' => 'sometimes|file|mimes:pdf,xls,xlsx,doc,docx,txt,png,jpg,jpeg',
            'contentimprove' => 'sometimes|nullable|string',
        ]);
    }

    public static function decimalToTime($decimalHours)
    {
        $hours = floor($decimalHours); // Get the whole number part for hours
        $minutes = ($decimalHours - $hours) * 60; // Get the decimal part and convert to minutes

        return sprintf('%02d:%02d', $hours, round($minutes)); // Format as HH:MM
    }
}
