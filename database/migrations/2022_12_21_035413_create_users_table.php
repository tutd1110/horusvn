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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fullname', 255)->default('');
            $table->date('date_official')->nullable();
            $table->string('phone', 255)->default('');
            $table->string('email', 255)->default('');
            $table->integer('department_id')->default(null);
            $table->date('birthday')->default(null);
            $table->integer('position')->default(0);
            $table->integer('permission')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('place_id')->nullable();
            $table->string('place_name', 255)->nullable();
            $table->string('face_image_url', 255)->nullable();
            $table->string('avatar', 255)->default('');
            $table->string('password', 255)->default('');
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('check_type')->default(1);
            $table->string('user_code', 255)->nullable();
            $table->integer('wage_now')->nullable();
            $table->tinyInteger('user_status')->default(1);

            $table->softDeletes();
            $table->unique('email');
            $table->index(['id', 'email', 'fullname', 'department_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
