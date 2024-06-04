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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('store_id'); // phan biet order rice or dynamic
            $table->string('items');
            $table->enum('status',['PENDING','COMPLETED'])->default('PENDING');
            $table->integer('total_amount');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['id','user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order');
    }
};
