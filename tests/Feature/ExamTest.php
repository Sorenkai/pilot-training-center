<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function instructor_can_create_exam()
    {
        $user = User::factory()->create(['id' => 10000001]);
        $user->groups()->attach(4, ['area_id' => 2]);

        $this->actingAs($user)->assertTrue(Gate::inspect('create', \App\Models\Exam::class)->allowed());
    }

    #[Test]
    public function user_cannot_create_exam()
    {
        $user = User::factory()->create(['id' => 10000001]);
        $this->actingAs($user)->assertTrue(Gate::inspect('create', \App\Models\Exam::class)->denied());
    }

    #[Test]
    public function instructor_can_store_exam()
    {
        $user = User::factory()->create(['id' => 10000001]);
        $user->groups()->attach(4, ['area_id' => 2]);

        $this->actingAs($user)->assertTrue(Gate::inspect('store', \App\Models\Exam::class)->allowed());
        $user2 = User::factory()->create(['id' => 10000002]);
        $attributes = [
            'rating' => \App\Models\PilotRating::find($this->faker->numberBetween(1, 7))->id,
            'score' => $this->faker->numberBetween(0, 100),
            'user' => $user->id,
            'url' => 'https://example.com',
            'issued_by' => $user2->id,
        ];

        $response = $this->actingAs($user)->post('/exam/store', $attributes)->assertStatus(302);
    }

    #[Test]
    public function user_cannot_store_exam()
    {
        $user = User::factory()->create(['id' => 10000001]);
        $this->actingAs($user)->assertTrue(Gate::inspect('store', \App\Models\Exam::class)->denied());
        $user2 = User::factory()->create(['id' => 10000002]);
        $attributes = [
            'rating' => \App\Models\PilotRating::find($this->faker->numberBetween(1, 7))->id,
            'score' => $this->faker->numberBetween(0, 100),
            'user_id' => $user->id,
            'issued_by' => $user2->id,
        ];

        $response = $this->actingAs($user)->post('/exam/store', $attributes)->assertStatus(403);
    }
}
