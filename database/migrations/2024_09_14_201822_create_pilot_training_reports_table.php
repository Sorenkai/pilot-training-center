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
        Schema::create('pilot_training_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pilot_training_id');
            $table->unsignedBigInteger('written_by_id')->nullable();
            $table->date('report_date');
            $table->text('content');
            $table->text('contentimprove')->nullable();
            $table->string('position')->nullable();
            $table->boolean('draft')->default(false);
            $table->timestamps();

            $table->foreign('pilot_training_id')->references('id')->on('pilot_trainings')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('written_by_id')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilot_training_reports');
    }
};
