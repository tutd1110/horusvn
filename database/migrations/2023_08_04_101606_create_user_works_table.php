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
        Schema::create('user_works', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable();
            $table->date('month')->nullable();
            $table->float('total_day', 8, 2)->nullable();
            $table->float('total_hour', 8, 2)->nullable();
            $table->float('total_effort_hour', 8, 2)->nullable();
            $table->integer('warrior_1')->nullable();
            $table->integer('warrior_2')->nullable();
            $table->integer('warrior_3')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['id', 'user_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_works');
    }
};
