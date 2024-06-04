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
        Schema::create('order_stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('phone')->nullable();
            $table->enum('type',['RICE','DYNAMIC'])->default('RICE');
            $table->integer('price');
            $table->integer('max_item');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['id','name','phone']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_store');
    }
};
