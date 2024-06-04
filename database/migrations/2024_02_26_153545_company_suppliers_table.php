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
        Schema::create('company_suppliers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255)->default('');
            $table->string('phone', 255)->nullable();
            $table->string('tax_code', 255)->nullable();
            $table->string('address', 255)->nullable();
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
        Schema::dropIfExists('company_suppliers');
    }
};
