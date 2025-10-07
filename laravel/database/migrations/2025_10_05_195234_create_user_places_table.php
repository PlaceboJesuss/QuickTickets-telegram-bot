<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_places', function (Blueprint $table) {
            $table->unsignedBigInteger('place_id');
            $table->unsignedBigInteger('chat_id');

            $table->primary(['place_id', 'chat_id']);

            $table->foreign('place_id')->references('id')->on('places')->onDelete('cascade');
            $table->foreign('chat_id')->references('chat_id')->on('telegram_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_places');
    }
};
