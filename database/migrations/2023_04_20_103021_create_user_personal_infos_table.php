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
        Schema::create('user_personal_infos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable();
            $table->string('fullname', 255)->nullable();
            $table->date('birthday')->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('gender', 255)->nullable();
            $table->string('id_number', 255)->nullable();
            $table->date('date_of_issue')->nullable();
            $table->string('place_of_issue', 255)->nullable();
            $table->string('hometown', 255)->nullable();
            $table->string('current_place', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->unique('user_id');
            $table->index(['id', 'user_id', 'fullname', 'birthday', 'phone', 'id_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_personal_infos');
    }
};
