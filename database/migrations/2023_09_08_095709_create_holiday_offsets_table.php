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
        Schema::create('holiday_offsets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('holiday_id')->nullable();
            $table->time('offset_start_time')->nullable();
            $table->time('offset_end_time')->nullable();
            $table->date('offset_date')->nullable();
            $table->float('workday', 4, 2)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(['id', 'holiday_id', 'offset_start_time', 'offset_start_time', 'offset_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('holiday_offsets');
    }
};
