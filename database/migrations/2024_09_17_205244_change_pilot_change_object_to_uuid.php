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

        $connection = Schema::getConnection()->getDriverName();

        if ($connection === 'sqlite') {

            /* SQLITE databases */
            /* WARNING: SQLITE will not copy the data */

            Schema::dropIfExists('pilot_training_object_attachments');

            // Then, create a new table with the desired structure
            Schema::create('pilot_training_object_attachments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->morphs('object');
                $table->string('file_id');
                $table->boolean('hidden')->default(false);
                $table->timestamps();
            });

        } else {

            /* MYSQL and other databases */

            // Step 1: Add a new UUID column
            Schema::table('pilot_training_object_attachments', function (Blueprint $table) {
                $table->uuid('uuid')->first();
            });

            // Step 2: Populate the new uuid column with UUIDs
            DB::table('pilot_training_object_attachments')->get()->each(function ($item) {
                DB::table('pilot_training_object_attachments')
                    ->where('id', $item->id)
                    ->update(['uuid' => Str::uuid()]);
            });

            // Step 3: Drop the old primary key first
            Schema::table('pilot_training_object_attachments', function (Blueprint $table) {
                $table->dropColumn('id');
            });

            // Step 4: Now set the 'uuid' column as the primary key
            Schema::table('pilot_training_object_attachments', function (Blueprint $table) {
                $table->primary('uuid');
            });

            // Step 5: Rename the 'uuid' column to 'id'
            Schema::table('pilot_training_object_attachments', function (Blueprint $table) {
                $table->renameColumn('uuid', 'id');
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uuid', function (Blueprint $table) {
            //
        });
    }
};
