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
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->integer('order');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('quotation_detail_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('hour')->default(0);
            // $table->double('rate')->default(0);
            // $table->double('total')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('quotation_detail_id')->references('id')->on('quotation_details')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_details');
    }
};
