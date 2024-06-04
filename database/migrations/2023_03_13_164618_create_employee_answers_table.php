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
        Schema::create('employee_answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('review_id')->nullable();
            $table->bigInteger('question_review_id')->nullable();
            $table->bigInteger('employee_id')->nullable();
            $table->text('employee_answer')->nullable();
            //0: member answer, 0.5: mentor answer, 1: leader answer and 2: pm answer
            $table->decimal('type', 2, 1)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(
                [
                    'id',
                    'review_id',
                    'question_review_id',
                    'employee_id',
                    'employee_answer',
                    'type'
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
        Schema::dropIfExists('employee_answers');
    }
};
