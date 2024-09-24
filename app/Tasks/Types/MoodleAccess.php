<?php

namespace App\Tasks\Types;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Http\Controllers\PilotTrainingActivityController;
use App\Models\Task;
use App\Notifications\TrainingCustomNotification;
use Illuminate\Support\Facades\Auth;

class MoodleAccess extends Types
{

    public function getName()
    {
        return 'Moodle Access';
    }

    public function getIcon()
    {
        return 'fa-key';
    }

    public function getText(Task $model)
    {
        if ($model->subjectTrainingRating) {
            return 'Grant Moodle access for ' . $model->subjectTrainingRating->name;
        } else {
            return 'Grant Moodle access for ' . $model->subjectTraining->getInlineRatings(true);
        }
    }

    public function getLink(Task $model)
    {
        return false;
    }

    public function create(Task $model)
    {
        parent::onCreated($model);
    }

    public function complete(Task $model)
    {
        PilotTrainingActivityController::create($model->subjectTraining->id, 'COMMENT', null, null, $model->assignee->id, 'Moodle access granted.');
        parent::onCompleted($model);
    }

    public function decline(Task $model)
    {
        parent::onDeclined($model);
    }

    public function showConnectedRatings()
    {
        return true;
    }

    public function requireRatingSelection()
    {
        return true;
    }

    public function allowNonVatsimRatings()
    {
        return false;
    }

    public function isApproval()
    {
        return Setting::get('divisionApiEnabled', false);
    }
}
