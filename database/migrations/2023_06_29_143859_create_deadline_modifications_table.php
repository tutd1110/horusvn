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
        Schema::create('deadline_modifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('task_id')->nullable();
            $table->bigInteger('task_deadline_id')->nullable();
            $table->date('original_deadline')->nullable();
            $table->date('requested_deadline');
            $table->text('reason')->nullable();
            $table->integer('status')->default(0); //0:UnApproved 1: approved, 2: rejected
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(['id', 'user_id', 'task_id', 'original_deadline', 'requested_deadline', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deadline_modifications');
    }
};
