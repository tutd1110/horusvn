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
        Schema::create('petitions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('start_time_change')->nullable();
            $table->time('end_time_change')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('reason')->nullable();
            $table->integer('check_type')->default(1); //1: Chấm công qua cam, 2 chấm công thủ công
            $table->integer('type')->nullable(); //1: Đi muộn về sớm, 2:..., 3: Nghỉ việc ...
            //1: Nghỉ phép có/không lương nửa ngày(sáng), 2: Nghỉ phép có/không lương nửa ngày(chiều) ...
            $table->integer('type_off')->nullable();
            $table->integer('status')->nullable(); //null: Đang chờ, 1: Đã duyệt, 2: Từ chối
            $table->text('rejected_reason')->nullable();
            $table->integer('type_paid')->nullable(); //0: not paid, 1: paid
            $table->integer('read')->nullable(); //0: not read, 1: read
            $table->integer('infringe')->nullable();
            $table->integer('type_go_out')->nullable();
            $table->bigInteger('user_go_out_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(['id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('petitions');
    }
};
