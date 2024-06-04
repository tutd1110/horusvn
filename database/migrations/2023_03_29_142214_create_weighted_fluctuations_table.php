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
        Schema::create('weighted_fluctuations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('task_id')->nullable();
            $table->bigInteger('project_id')->nullable();
            $table->float('weight', 8, 2)->nullable();
            //0: the weight will be added for testers,
            //1: the weight will be added for employees, who do the task's origin employee
            //2: the weight left after changed for origin employees task
            $table->integer('type')->nullable();
            //0: bug, 1: feedback, 2: Add Design, 3: Edit Design
            //null: there is no issue
            $table->integer('issue')->nullable(); //task_assignments type
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(
                ['id', 'user_id', 'task_id', 'project_id', 'type', 'weight']
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('weighted_fluctuations');
    }
};
