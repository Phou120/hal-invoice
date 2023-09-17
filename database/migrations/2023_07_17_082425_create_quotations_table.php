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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number');
            $table->unsignedBigInteger('quotation_type_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            // $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('quotation_name');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->text('note')->nullable();
            // $table->double('sub_total')->default(0);
            // $table->double('discount')->default(0);
            // $table->double('tax')->default(0);
            // $table->double('total')->default(0);
            $table->enum('status', ['created', 'inprogress', 'approved', 'completed', 'cancelled'])->default('created');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('quotation_type_id')
                ->references('id')
                ->on('quotation_types')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('quotations');
    }
};
