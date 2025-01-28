<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\PilotRating;
use App\Models\PilotTraining;
use App\Models\User;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function createTheory($prefillUserId = null)
    {
        $this->authorize('create', Exam::class);

        if ($prefillUserId) {
            $users = User::where('id', $prefillUserId)->with(['pilotTrainings', 'pilotTrainings.pilotRatings'])->get();
        } else {
            $users = User::with(['pilotTrainings', 'pilotTrainings.pilotRatings'])->get();
        }

        $ratings = PilotRating::whereIn('vatsim_rating', [1, 3, 7, 15, 31])->get();

        return view('exam.create', compact('users', 'ratings', 'prefillUserId'));
    }

    public function createPractical($prefillUserId = null)
    {
        $this->authorize('create', Exam::class);

        if ($prefillUserId) {
            $users = User::where('id', $prefillUserId)->with(['pilotTrainings', 'pilotTrainings.pilotRatings'])->get();
        } else {
            $users = User::with(['pilotTrainings', 'pilotTrainings.pilotRatings'])->get();
        }

        $ratings = PilotRating::whereIn('vatsim_rating', [1, 3, 7, 15, 31])->get();

        return view('exam.practical.create', compact('users', 'ratings', 'prefillUserId'));
    }

    public function storeTheory(Request $request)
    {
        $this->authorize('store', [Exam::class]);

        $data = [];
        $data = request()->validate([
            'user' => 'required|numeric|exists:App\Models\User,id',
            'training' => 'required|numeric|exists:App\Models\PilotTraining,id',
            'url' => 'required|url',
            'score' => 'required|numeric|min:0|max:100',
        ]);

        $user = User::find($data['user']);
        $training = PilotTraining::find($data['training']);

        $exam = Exam::create([
            'type' => 'THEORY',
            'pilot_training_id' => $training->id,
            'pilot_rating_id' => $training->pilotRatings()->first()->id,
            'url' => $data['url'],
            'score' => $data['score'],
            'user_id' => $user->id,
            'issued_by' => \Auth::user()->id,
        ]);

        return redirect()->intended(route('exam.create'))->withSuccess($user->name . "'s theory result saved");
    }

    public function storePractical(Request $request)
    {
        $this->authorize('store', [Exam::class]);

        $data = [];
        // dd($request);
        // dd(request()->file('files'));
        $data = request()->validate([
            'user' => 'required|numeric|exists:App\Models\User,id',
            'training' => 'required|numeric|exists:App\Models\PilotTraining,id',
            'result' => 'required',
            'files.*' => 'sometimes|file|mimes:pdf,xls,xlsx,doc,docx,txt,png,jpg,jpeg',
        ]);

        $user = User::find($data['user']);
        $training = PilotTraining::find($data['training']);

        $exam = Exam::create([
            'type' => 'PRACTICAL',
            'pilot_training_id' => $training->id,
            'pilot_rating_id' => $training->pilotRatings()->first()->id,
            'result' => $data['result'],
            'user_id' => $user->id,
            'issued_by' => \Auth::user()->id,
        ]);

        unset($data['files']);

        ExamObjectAttachmentController::saveAttachments($request, $exam);

        return redirect()->intended(route('exam.practical.create'))->withSuccess($user->name . "'s exam result saved");
    }
}
