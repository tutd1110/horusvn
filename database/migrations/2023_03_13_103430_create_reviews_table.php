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
        Schema::create('reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('employee_id')->nullable();
            $table->integer('period')->nullable(); //0: 2 weeks, 1: 2 months, 2: 6 months
            //progress 0: waiting employee, 0.5: waiting mentor, 1: waiting leader,
            //2: waiting pm, 3: waiting director and 4: completed
            $table->decimal('progress', 2, 1)->nullable();
            $table->date('start_date')->nullable();
            $table->date('next_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(
                [
                    'id',
                    'employee_id',
                    'period',
                    'progress',
                    'start_date'
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
        Schema::dropIfExists('reviews');
    }
};
