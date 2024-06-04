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
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('project_id')->nullable();
            $table->bigInteger('task_id')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('tester_id')->nullable();
            $table->integer('assigned_department_id')->nullable();
            $table->bigInteger('assigned_user_id')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->integer('status')->nullable();
            $table->integer('tag_test')->nullable(); //0: Test before done, 1: Test after done, 2: Test after upgame
            $table->text('note')->nullable();
            $table->integer('level')->nullable(); //0: Thấp, 1: Cao
            $table->integer('type')->nullable(); //0: bug, 1: feedback, 2: Add Design, 3: Edit Design
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(['id', 'project_id', 'task_id', 'tester_id',
                'assigned_user_id', 'assigned_department_id', 'start_date', 'tag_test', 'status', 'level', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_assignments');
    }
};
