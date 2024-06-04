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
        Schema::create('user_checkouts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_code', 255)->nullable();
            $table->time('check_out')->nullable();
            $table->boolean('final_checkout')->default(0);
            $table->date('date')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['id', 'user_code', 'check_out', 'final_checkout', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_checkouts');
    }
};
