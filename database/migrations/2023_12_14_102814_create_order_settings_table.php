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
        Schema::create('order_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('bank_qr_code')->nullable();
            $table->dateTime('time_alert')->nullable();
            $table->string('content_alert')->nullable();
            $table->date('date')->nullable();
            $table->dateTime('start_time')->nullable()->comment('start time order');
            $table->dateTime('end_time')->nullable()->comment('end time order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['id','time_alert']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_config');
    }
};
