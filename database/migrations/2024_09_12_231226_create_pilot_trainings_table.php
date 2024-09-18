<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pilot_trainings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('status')->default(0)->comment('-4: Closed by system, -3: Closed on student’s request, -2: Closed on TA request, -1: Completed, 0: In queue, 1: Pre-training, 2: Active training, 3: Awaiting exam');
            $table->boolean('english_only_training');
            $table->tinyInteger('experience')->nullable();
            $table->text('comment')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->unsignedInteger('paused_length')->default(0);
            $table->string('closed_reason')->nullable();
            $table->timestamps();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilot_trainings');
    }
};
