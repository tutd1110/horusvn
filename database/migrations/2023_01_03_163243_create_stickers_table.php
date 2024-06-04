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
        Schema::create('stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255)->nullable();
            $table->integer('department_id')->nullable();
            $table->float('level_1', 8, 2)->nullable();
            $table->float('level_2', 8, 2)->nullable();
            $table->float('level_3', 8, 2)->nullable();
            $table->float('level_4', 8, 2)->nullable();
            $table->float('level_5', 8, 2)->nullable();
            $table->float('level_6', 8, 2)->nullable();
            $table->float('level_7', 8, 2)->nullable();
            $table->float('level_8', 8, 2)->nullable();
            $table->float('level_9', 8, 2)->nullable();
            $table->float('level_10', 8, 2)->nullable();
            $table->integer('ordinal_number')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(['id', 'department_id', 'ordinal_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stickers');
    }
};
