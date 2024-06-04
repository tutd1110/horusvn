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
        Schema::create('review_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('review_id')->nullable();
            $table->bigInteger('employee_id')->nullable();
            $table->bigInteger('employee_answer_id')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(
                ['id', 'review_id', 'employee_id', 'employee_answer_id', 'file_path']
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
        Schema::dropIfExists('review_files');
    }
};
