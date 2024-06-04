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
        Schema::create('task_timings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('task_id')->nullable();
            $table->bigInteger('task_assignment_id')->nullable();
            $table->bigInteger('sticker_id')->nullable();
            $table->integer('priority')->nullable();
            $table->float('weight', 8, 2)->nullable();
            $table->float('time_spent', 8, 2)->nullable();
            $table->float('estimate_time', 8, 2)->nullable();
            $table->date('work_date')->nullable();
            $table->text('description')->nullable();
            $table->integer('type')->nullable(); //0: task, 1:bug, 2:feedback
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index([
                'id', 'task_id', 'sticker_id', 'priority', 'weight', 'time_spent', 'estimate_time', 'work_date', 'type'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_timings');
    }
};
