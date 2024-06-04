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
        Schema::create('user_job_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('position')->nullable();
            $table->date('start_date')->nullable();
            $table->date('official_start_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->text('disrupted_employment')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->unique('user_id');
            $table->index(['id', 'user_id', 'department_id', 'position', 'start_date', 'official_start_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_job_details');
    }
};
