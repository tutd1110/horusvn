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
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 765)->nullable();
            $table->string('name', 765)->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->float('time', 8, 2)->nullable();
            $table->text('description')->nullable();
            $table->integer('priority')->nullable();
            $table->bigInteger('sticker_id')->nullable();
            $table->bigInteger('department_id')->nullable();
            $table->float('weight', 8, 2)->nullable();
            $table->bigInteger('project_id')->nullable();
            $table->bigInteger('task_parent')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('real_start_time')->nullable();
            $table->timestamp('real_end_time')->nullable();
            $table->float('time_pause', 8, 2)->nullable();
            $table->float('real_time', 8, 2)->nullable();
            $table->date('deadline')->nullable();
            $table->bigInteger('deleted_by')->nullable();
            $table->bigInteger('root_parent')->nullable();
            $table->integer('progress')->default(0);

            $table->softDeletes();
            $table->index(['id', 'task_parent', 'name', 'description', 'department_id', 'sticker_id', 'priority',
                'weight', 'progress', 'project_id', 'user_id', 'status', 'deadline']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
