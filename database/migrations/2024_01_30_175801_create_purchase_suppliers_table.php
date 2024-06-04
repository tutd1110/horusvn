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
        Schema::create('purchase_suppliers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('purchase_id')->nullable();
            $table->bigInteger('company_id')->nullable();
            $table->string('price', 255)->nullable();
            $table->date('delivery_time')->nullable();
            $table->text('path')->nullable();
            $table->text('path_po')->nullable();
            $table->integer('status')->nullable();
            $table->text('note')->nullable();
            $table->integer('user_created')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_suppliers');
    }
};
