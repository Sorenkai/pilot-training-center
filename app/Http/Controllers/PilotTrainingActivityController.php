<?php

namespace App\Http\Controllers;

use App\Models\PilotTraining;
use App\Models\PilotTrainingActivity;
use Illuminate\Http\Request;

class PilotTrainingActivityController extends Controller
{
    public static $activityTypes = ['STATUS' => true, 'INSTRUCTOR' => true, 'PAUSE' => true, 'COMMENT' => true, 'PRETRAINING' => true, 'EXAM' => true];

    public static function create(int $trainingId, string $type, ?int $new_data = null, ?int $old_data = null, ?int $userId = null, ?string $comment = null)
    {

        try {
            PilotTrainingActivityController::$activityTypes[$type];
        } catch (\Exception $e) {
            throw new \App\Exceptions\InvalidTrainingActivityType('The type ' . $type . ' is not supported.');
        }

        $activity = new PilotTrainingActivity();
        $activity->pilot_training_id = $trainingId;
        $activity->type = $type;
        $activity->new_data = $new_data;
        $activity->old_data = $old_data;
        $activity->triggered_by_id = $userId;
        $activity->comment = $comment;
        $activity->save();

        return $activity;
    }

    public function storeComment(Request $request)
    {
        $data = request()->validate([
            'pilot_training_id' => 'required|exists:App\Models\PilotTraining,id',
            'comment' => 'required|string|max:512',
            'update_id' => 'nullable',
        ]);

        $this->authorize('comment', [PilotTrainingActivity::class, PilotTraining::find($data['pilot_training_id'])]);

        if (isset($data['update_id'])) {
            $activity = PilotTrainingActivity::find($data['update_id']);
            if ($activity == null) {
                return back()->withInput()->withErrors('Could not find commend to update.');
            }

            $activity->comment = $data['comment'];
            $activity->save();

            return redirect()->back()->withSuccess('Comment updated.');
        }

        $activity = new PilotTrainingActivity();
        $activity->pilot_training_id = $data['pilot_training_id'];
        $activity->triggered_by_id = \Auth::user()->id;
        $activity->type = 'COMMENT';
        $activity->comment = $data['comment'];
        $activity->save();

        return redirect()->back()->withSuccess('Comment created.');
    }
}
