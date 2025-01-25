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
        Schema::table('pilot_training_reports', function (Blueprint $table) {
            $table->decimal('instructor_hours')->nullable()->after('contentimprove');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_training_reports', function (Blueprint $table) {
            $table->dropColumn('instructor_hours');
        });
    }
};
