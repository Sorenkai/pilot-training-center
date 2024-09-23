<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\PilotTraining;
use App\Models\PilotTrainingObjectAttachment;
use App\Models\PilotTrainingReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PilotTrainingObjectAttachmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $report;

    private $user;

    /**
     * Provide report to use throughout the tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['id' => 10000005]);
        $this->report = PilotTrainingReport::factory()->create([
            'pilot_training_id' => PilotTraining::factory()->create([
                'user_id' => $this->user->id,
            ])->id,
            'written_by_id' => User::factory()->create([
                'id' => 10000001,
            ])->id,
        ]);

        $this->report->author->groups()->attach(4, ['area_id' => 2]);
    }

    /**
     * Automatically delete the files that were uploaded during the tests.
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        Storage::deleteDirectory('/public');
        parent::tearDown();
    }

    #[Test]
    public function instructor_can_upload_an_attachment()
    {
        $this->withoutExceptionHandling();
        $instructor = $this->report->author;

        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        $response = $this->actingAs($instructor)->postJson(route('pilot.training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file]);
        $id = $response->json('id');

        $this->assertDatabaseHas('pilot_training_object_attachments', ['id' => $id]);
        $attachments = PilotTrainingObjectAttachment::find($id);
        Storage::disk('test')->assertExists($attachments->first()->file->full_path);
    }


    #[Test]
    public function student_cant_upload_an_attachment()
    {
        $student = $this->user;
        $file = UploadedFile::fake()->image($this->faker->word);

        $response = $this->actingAs($student)->postJson(route('pilot.training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file]);
        $response->assertStatus(403);
        $id = $response->json('id');

        $this->assertDatabaseMissing('pilot_training_object_attachments', ['id' => $id]);
        $this->assertNull(File::find($id));
    }

    #[Test]
    public function instructor_can_see_attachments()
    {
        $instructor = $this->report->author;
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        $id = $this->actingAs($instructor)
            ->postJson(route('pilot.training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file])
            ->json('id')[0];

        $this->followingRedirects()->get(route('pilot.training.object.attachment.show', ['attachment' => $id]))
            ->assertStatus(200);
    }

    #[Test]
    public function student_can_see_not_hidden_attachment()
    {
        $student = $this->report->pilotTraining->user;
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        // We force-update report to not be a draft
        $this->report->update(['draft' => 0]);

        $id = $this->actingAs($this->report->author)
            ->postJson(route('pilot.training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file])
            ->json('id')[0];

        $this->actingAs($student)->followingRedirects()
            ->get(route('pilot.training.object.attachment.show', ['attachment' => $id]))
            ->assertStatus(200);
    }

    #[Test]
    public function instructor_can_access_hidden_attachment()
    {
        $instructor = $this->report->author;
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        $id = $this->actingAs($instructor)
            ->postJson(route('pilot.training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report, 'hidden' => true]), ['file' => $file])
            ->json('id')[0];

        $this->actingAs($instructor)->followingRedirects()
            ->get(route('pilot.training.object.attachment.show', ['attachment' => $id]))
            ->assertStatus(200);
    }
}