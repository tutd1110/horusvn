<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timesheet_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_code', 255)->default('');
            $table->text('detected_image_url')->nullable();
            $table->string('device_id', 255)->nullable();
            $table->string('person_title', 255)->nullable();
            $table->string('time_int', 255)->nullable();
            $table->time('time')->nullable();
            $table->date('date')->nullable();
            $table->string('partner_id', 255)->nullable();
            $table->text('json_data')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['id', 'user_code', 'time', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('timesheet_details');
    }
};
