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
        Schema::create('oauth_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_id');
            $table->unsignedBigInteger('access_token_id');
            $table->boolean('revoked');
            $table->dateTime('expires_at');
            $table->timestamps();
            $table->foreign('access_token_id')
                ->references('id')
                ->on('oauth_access_tokens')   
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
        Schema::dropIfExists('oauth_refresh_tokens');
    }
};
