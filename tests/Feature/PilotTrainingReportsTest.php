<?php

namespace Tests\Feature;

use App\Models\PilotTraining;
use App\Models\PilotTrainingReport;
use App\Models\User;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PilotTrainingReportsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function instructor_can_access_training_reports()
    {
        $training = PilotTraining::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);

        $lesson = Lesson::factory()->create();

        $instructor = User::factory()->create(['id' => 10000400]);
        $instructor->groups()->attach(4, ['area_id' => 2]);
        $training->instructors()->attach($instructor, ['expire_at' => now()->addYears(10)]);
    
        $report = PilotTrainingReport::factory()->create([
            'pilot_training_id' => $training->id,
            'written_by_id' => $instructor->id,
            'report_date' => now()->addYear(),
            'lesson_id' => $lesson->id,
            'content' => "Lorem Ipsum",
            'contentimprove' => null,
            'draft' => false,
        ]);

        $this->actingAs($instructor)->assertTrue(Gate::inspect('view', $report, [$training->user, $report])->allowed());
    }

    #[Test]
    public function student_can_access_training_reports()
    {
        $training = PilotTraining::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);

        $lesson = Lesson::factory()->create();

        $instructor = User::factory()->create(['id' => 10000400]);
    
        $report = PilotTrainingReport::factory()->create([
            'pilot_training_id' => $training->id,
            'written_by_id' => $instructor->id,
            'report_date' => now()->addYear(),
            'lesson_id' => $lesson->id,
            'content' => "Lorem Ipsum",
            'contentimprove' => null,
            'draft' => false,
        ]);

        $this->actingAs($training->user)->assertTrue(Gate::inspect('view', $report, [$training->user, $report])->allowed());
    }

    #[Test]
    public function regular_user_cant_access_training_reports()
    {
        $training = PilotTraining::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);

        $lesson = Lesson::factory()->create();

        $instructor = User::factory()->create(['id' => 10000400]);
    
        $report = PilotTrainingReport::factory()->create([
            'pilot_training_id' => $training->id,
            'written_by_id' => $instructor->id,
            'report_date' => now()->addYear(),
            'lesson_id' => $lesson->id,
            'content' => "Lorem Ipsum",
            'contentimprove' => null,
            'draft' => false,
        ]);
        $otherUser = User::factory()->create(['id' => 10000134]);
        $this->actingAs($otherUser)->assertTrue(Gate::inspect('view', $report, [$training->user, $report])->denied());
    }

    #[Test]
    public function student_cant_access_draft_report()
    {
        $training = PilotTraining::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000067])->id,
        ]);

        $lesson = Lesson::factory()->create();

        $instructor = User::factory()->create(['id' => 10000159]);
        $instructor->groups()->attach(4, ['area_id' => 2]);
    
        $report = PilotTrainingReport::factory()->create([
            'pilot_training_id' => $training->id,
            'written_by_id' => $instructor->id,
            'report_date' => now()->addYear(),
            'lesson_id' => $lesson->id,
            'content' => "Lorem Ipsum",
            'contentimprove' => null,
            'draft' => true,
        ]);

        $this->actingAs($report->pilotTraining->user)->assertTrue(Gate::inspect('view', $report)->denied());
    }

    #[Test]
    public function instructor_can_access_draft_report()
    {
        $training = PilotTraining::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000042])->id,
        ]);

        $lesson = Lesson::factory()->create();

        $instructor = User::factory()->create(['id' => 10000080]);
        $instructor->groups()->attach(4, ['area_id' => 2]);
    
        $report = PilotTrainingReport::factory()->create([
            'pilot_training_id' => $training->id,
            'written_by_id' => $instructor->id,
            'report_date' => now()->addYear(),
            'lesson_id' => $lesson->id,
            'content' => "Lorem Ipsum",
            'contentimprove' => null,
            'draft' => true,
        ]);
        $training->instructors()->attach($instructor, ['expire_at' => now()->addYear()]);
        $this->actingAs($instructor)->assertTrue(Gate::inspect('view', $report)->allowed());
    }
}
