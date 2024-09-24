<?php

namespace App\Tasks\Types;

use anlutro\LaravelSettings\Facade as Setting;
use App\Http\Controllers\PilotTrainingActivityController;
use App\Models\Task;

class ExamRequest extends Types
{
    public function getName()
    {
        return 'Practical Exam';
    }

    public function getIcon()
    {
        return 'fa-medal';
    }

    public function getText(Task $model)
    {
        if ($model->subjectTrainingRating) {
            return 'Request practical exam for ' . $model->subjectTrainingRating->name;
        } else {
            return 'Request practical exam for ' . $model->subjectTraining->getInlineRatings(true);
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
        PilotTrainingActivityController::create($model->subjectTraining->id, 'COMMENT', null, null, $model->assignee->id, 'Exam requested');
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
