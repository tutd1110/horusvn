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
        Schema::create('employee_review_points', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('review_id')->nullable();
            $table->bigInteger('content_review_id')->nullable();
            $table->bigInteger('employee_id')->nullable();
            $table->bigInteger('mentor_id')->nullable();
            $table->bigInteger('leader_id')->nullable();
            $table->bigInteger('pm_id')->nullable();
            $table->integer('employee_point')->nullable();
            $table->integer('mentor_point')->nullable();
            $table->integer('leader_point')->nullable();
            $table->integer('pm_point')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(
                [
                    'id',
                    'review_id',
                    'content_review_id',
                    'employee_id',
                    'mentor_id',
                    'leader_id',
                    'pm_id',
                    'employee_point',
                    'mentor_point',
                    'leader_point',
                    'pm_point'
                ]
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
        Schema::dropIfExists('employee_review_points');
    }
};
