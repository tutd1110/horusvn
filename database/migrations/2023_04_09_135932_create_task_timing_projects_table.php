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
        Schema::create('task_timing_projects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('task_id')->nullable();
            $table->bigInteger('task_timing_id')->nullable();
            $table->bigInteger('project_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(['id', 'task_id', 'task_timing_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_timing_projects');
    }
};
