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
        Schema::create('user_equipment_handovers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable();
            $table->date('handover_date')->nullable();
            $table->string('name', 255)->nullable();
            $table->text('detail')->nullable();
            $table->integer('status')->nullable(); //0: using, 1: recall
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->softDeletes();
            $table->index(['id', 'user_id', 'handover_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_equipment_handovers');
    }
};
