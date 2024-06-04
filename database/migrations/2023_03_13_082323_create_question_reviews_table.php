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
        Schema::create('question_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('question')->nullable();
            $table->integer('period')->nullable(); //0: 2 weeks or 2 months, 1: 6 months
            $table->integer('type')->nullable(); //0: member, 1: leader/pm
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(['id', 'question', 'period', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('question_reviews');
    }
};
