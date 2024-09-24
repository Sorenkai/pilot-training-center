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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['THEORY', 'PRACTICAL']);
            $table->enum('result', ['PASS', 'PARTIAL PASS', 'FAIL'])->nullable();
            $table->unsignedInteger('pilot_rating_id');
            $table->text('url')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('issued_by');
            $table->timestamps();

            $table->foreign('pilot_rating_id')->references('id')->on('pilot_ratings')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('issued_by')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam');
    }
};
