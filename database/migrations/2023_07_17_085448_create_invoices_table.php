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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->unsignedBigInteger('customer_id');
            // $table->unsignedBigInteger('currency_id');
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('invoice_name');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->text('note')->nullable();
            $table->string('type_quotation')->default(0);
            // $table->double('sub_total')->default(0);
            // $table->double('discount')->default(0);
            // $table->double('tax')->default(0);
            // $table->double('total')->default(0);
            $table->enum('status', ['created', 'approved', 'inprogress', 'completed', 'cancelled'])->default('created');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('quotation_id')->references('id')->on('quotations')->onUpdate('cascade')->onDelete('cascade');
            // $table->foreign('currency_id')->references('id')->on('currencies')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};
