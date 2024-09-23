<?php

namespace Tests\Feature;

use App\Models\PilotTraining;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PilotTrainingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function user_can_create_a_training_request()
    {
        Mail::fake();
        $this->withoutExceptionHandling();

        $user = User::factory()->create(['id' => 10000005]);
        $attributes = [
            'experience' => $this->faker->numberBetween(1, 3),
            'englishOnly' => (int) $this->faker->boolean,
            'comment' => '',
            'training_level' => \App\Models\PilotRating::find($this->faker->numberBetween(1, 7))->id,
        ];

        $this->actingAs($user)
            ->post('/pilot/training/store', $attributes)
            ->assertRedirect('/dashboard');
        
        $this->assertDatabaseHas('pilot_trainings', ['user_id' => $user->id]);

        Mail::assertNothingSent();
    }

    #[Test]
    public function guest_cant_create_pilot_training_request()
    {
        $attributes = [
            'experience' => $this->faker->numberBetween(1, 3),
            'englishOnly' => (int) $this->faker->boolean,
            'comment' => '',
            'training_level' => \App\Models\PilotRating::find($this->faker->numberBetween(1, 7))->id,
        ];

        $response = $this->post('/pilot/training/store', $attributes);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function moderator_can_update_training_request()
    {
        $moderator = User::factory()->create();

        $training = PilotTraining::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);

        $moderator->groups()->attach(4, ['area_id' => 2]); // pilot trainings dont have ares, so hardcoded

        $this->assertDatabaseHas('pilot_trainings', ['id' => $training->id]);

        $this->actingAs($moderator)
            ->patch($training->path(), $attributes = ['status' => 0])
            ->assertRedirect($training->path())
            ->assertSessionHas('success', 'Training successfully updated');

        $this->assertDatabaseHas('pilot_trainings', ['id' => $training->id, 'status' => $attributes['status']]);
    }

    #[Test]
    public function a_regular_user_cant_update_a_training()
    {
        $training = PilotTraining::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);
        $user = $training->user;
        $user->groups()->attach(3, ['area_id' => 2]);

        $this->assertDatabaseHas('pilot_trainings', ['id' => $training->id]);

        $this->actingAs($user)
            ->patch($training->path(), $attributes = ['status' => 0])
            ->assertStatus(403);
    }

    
    #[Test]
    public function instructor_can_update_the_trainings_status()
    {
        $training = PilotTraining::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);
        $instructor = User::factory()->create();
        $instructor->groups()->attach(4, ['area_id' => 2]);

        $this->assertDatabaseHas('pilot_trainings', ['id' => $training->id]);

        $this->actingAs($instructor)->patch(route('pilot.training.update.details', ['training' => $training->id]), ['status' => 0]);

        $this->assertDatabaseHas('pilot_trainings', ['id' => $training->id, 'status' => 0]);
    }
    
    
    #[Test]
    public function a_user_cant_update_their_own_training_request()
    {
        $training = PilotTraining::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);

        $this->actingAs($training->user)
            ->patch($training->path(), $attributes = ['status' => 0])
            ->assertStatus(403);
    }

    
    #[Test]
    public function an_instructor_can_be_added()
    {
        Mail::fake();
        $training = PilotTraining::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);
        $instructor = User::factory()->create();

        $instructor->groups()->attach(4, ['area_id' => 2]);

        $this->actingAs($instructor)
            ->patchJson(route('pilot.training.update.details', ['training' => $training]), ['instructors' => [$instructor->id]])
            ->assertStatus(302);

        $training->refresh();
        $this->assertTrue($training->instructors->contains($instructor));
    }

    
    #[Test]
    public function a_user_cant_request_a_new_training_if_they_already_have_one()
    {
        $user = User::factory()->create();
        $training = PilotTraining::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->assertTrue(Gate::inspect('apply', PilotTraining::class)->denied());
    }

}
