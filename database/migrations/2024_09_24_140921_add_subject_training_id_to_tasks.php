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
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('subject_training_rating_id')->nullable()->after('message');
            $table->unsignedBigInteger('subject_training_id')->after('subject_training_rating_id');
            $table->unsignedInteger('assignee_user_id')->constrained('users')->onDelete('cascade')->nullable()->after('subject_user_id');

            $table->foreign('subject_training_rating_id')->references('id')->on('pilot_ratings')->onDelete('cascade');
            $table->foreign('subject_training_id')->references('id')->on('pilot_trainings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign('tasks_subject_training_rating_id_foreign');
            $table->dropColumn('subject_training_rating_id');

            $table->dropForeign('tasks_subject_training_id_foreign');
            $table->dropColumn('tasks_subject_training_id');

        });
    }
};
