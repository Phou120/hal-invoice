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
        Schema::create('module_titles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_category_id');
            $table->string('name');
            $table->double('hour')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('module_category_id')
                ->references('id')
                ->on('module_categories')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('module_titles');
    }
};
